<?php
/**
 * Created by PhpStorm.
 * User: ant
 * Date: 16.03.2017
 * Time: 17:46
 */

namespace Kaula\TelegramBundle\Telegram;


use GuzzleHttp\Exception\ClientException;
use InvalidArgumentException;
use Kaula\TelegramBundle\Exception\KaulaTelegramBundleException;
use Kaula\TelegramBundle\Telegram\Command\HelpCommand;
use Kaula\TelegramBundle\Telegram\Command\StartCommand;
use Kaula\TelegramBundle\Telegram\Listener\GroupCreatedEvent;
use Kaula\TelegramBundle\Telegram\Listener\LeftChatMemberEvent;
use Kaula\TelegramBundle\Telegram\Listener\JoinChatMemberEvent;
use Kaula\TelegramBundle\Telegram\Listener\EventListenerInterface;
use Kaula\TelegramBundle\Telegram\Listener\ExecuteCommandsEvent;
use Kaula\TelegramBundle\Telegram\Listener\MigrateFromChatIdEvent;
use Kaula\TelegramBundle\Telegram\Listener\MigrateToChatIdEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use unreal4u\TelegramAPI\Abstracts\KeyboardMethods;
use unreal4u\TelegramAPI\Telegram\Methods\SendMessage;
use unreal4u\TelegramAPI\Telegram\Types\Message;
use unreal4u\TelegramAPI\Telegram\Types\Update;
use unreal4u\TelegramAPI\TgLog;

class Bot {

  // Update types
  const UT_MESSAGE = 'message';
  const UT_EDITED_MESSAGE = 'edited_message';
  const UT_CHANNEL_POST = 'channel_post';
  const UT_EDITED_CHANNEL_POST = 'edited_channel_post';
  const UT_INLINE_QUERY = 'inline_query';
  const UT_CHOSEN_INLINE_RESULT = 'chosen_inline_result';
  const UT_CALLBACK_QUERY = 'callback_query';

  // Event types

  const ET_NOTHING = 0;
  const ET_TEXT = 1;
  const ET_AUDIO = 2;
  const ET_DOCUMENT = 4;
  const ET_GAME = 8;
  const ET_PHOTO = 16;
  const ET_STICKER = 32;
  const ET_VIDEO = 64;
  const ET_VOICE = 128;
  const ET_CONTACT = 256;
  const ET_LOCATION = 512;
  const ET_VENUE = 1024;
  const ET_NEW_CHAT_MEMBER = 2048;
  const ET_LEFT_CHAT_MEMBER = 4096;
  const ET_NEW_CHAT_TITLE = 8192;
  const ET_NEW_CHAT_PHOTO = 16384;
  const ET_DELETE_CHAT_PHOTO = 32768;
  const ET_GROUP_CHAT_CREATED = 65536;
  const ET_SUPERGROUP_CHAT_CREATED = 131072;
  const ET_CHANNEL_CHAT_CREATED = 262144;
  const ET_MIGRATE_TO_CHAT_ID = 524288;
  const ET_MIGRATE_FROM_CHAT_ID = 1048576;
  const ET_PINNED_MESSAGE = 1048576;
  const ET_ANY = 2097151;

  /**
   * @var CommandBus
   */
  protected $bus;

  /**
   * Symfony container.
   *
   * @var ContainerInterface
   */
  protected $container;

  /**
   * Array of listeners for events (EventListenerInterface)
   *
   * @var array
   */
  protected $listeners = [];

  /**
   * Bot constructor.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * @internal param string $token
   */
  public function __construct(ContainerInterface $container) {
    $this->container = $container;
    $this->bus = new CommandBus($this);
    $this->bus->registerCommand(StartCommand::class)
      ->registerCommand(HelpCommand::class);

    // Add default listeners
    $this->addDefaultListeners();
  }

  /**
   * Adds default listeners.
   */
  protected function addDefaultListeners() {
    $this->addEventListener(self::ET_TEXT, ExecuteCommandsEvent::class)
      ->addEventListener(self::ET_NEW_CHAT_MEMBER, JoinChatMemberEvent::class)
      ->addEventListener(self::ET_LEFT_CHAT_MEMBER, LeftChatMemberEvent::class)
      ->addEventListener(self::ET_MIGRATE_TO_CHAT_ID,
        MigrateToChatIdEvent::class)
      ->addEventListener(self::ET_MIGRATE_FROM_CHAT_ID,
        MigrateFromChatIdEvent::class)
      ->addEventListener(self::ET_GROUP_CHAT_CREATED, GroupCreatedEvent::class);
  }

  /**
   * Handles update.
   *
   * @param \unreal4u\TelegramAPI\Telegram\Types\Update $update
   * @return void
   * @throws \InvalidArgumentException
   */
  public function handleUpdate(Update $update = NULL) {
    /** @var LoggerInterface $l */
    $l = $this->container->get('logger');

    // Scrap incoming data
    if (is_null($update)) {
      $update = $this->scrapIncomingData();
    }
    $update_type = $this->whatUpdateType($update);

    // Allow some debug info
    $l->debug('Bot is handling telegram update of type "{type}"',
      ['type' => $update_type, 'update' => print_r($update, TRUE)]);

    // Try to execute hook
    $this->executeHook($update);

    // Check type of the update and possibly process it
    switch ($update_type) {
      case self::UT_MESSAGE:
        $this->handleUpdateWithMessage($update);
        break;
    }
  }

  /**
   * Scrap incoming data into Update object.
   *
   * @return \unreal4u\TelegramAPI\Telegram\Types\Update
   */
  protected function scrapIncomingData() {
    $update_data = json_decode(file_get_contents('php://input'), TRUE);
    if (JSON_ERROR_NONE != json_last_error()) {
      throw new InvalidArgumentException(json_last_error_msg());
    }

    return new Update($update_data);
  }

  /**
   * Returns type of the update.
   *
   * @param \unreal4u\TelegramAPI\Telegram\Types\Update $update
   * @return string
   * @throws KaulaTelegramBundleException
   */
  protected function whatUpdateType(Update $update) {
    if (!is_null($update->message)) {
      return self::UT_MESSAGE;
    }
    if (!is_null($update->edited_message)) {
      return self::UT_EDITED_MESSAGE;
    }
    if (!is_null($update->channel_post)) {
      return self::UT_CHANNEL_POST;
    }
    if (!is_null($update->edited_channel_post)) {
      return self::UT_EDITED_CHANNEL_POST;
    }
    if (!is_null($update->inline_query)) {
      return self::UT_INLINE_QUERY;
    }
    if (!is_null($update->chosen_inline_result)) {
      return self::UT_CHOSEN_INLINE_RESULT;
    }
    if (!is_null($update->callback_query)) {
      return self::UT_CALLBACK_QUERY;
    }

    throw new KaulaTelegramBundleException('Unknown update type.');
  }

  /**
   * Returns type of the event.
   *
   * @param \unreal4u\TelegramAPI\Telegram\Types\Update $update
   * @return string
   */
  protected function whatEventType(Update $update) {
    if (is_null($update->message)) {
      throw new KaulaTelegramBundleException('Unable to tell event type: message object for this update is null.');
    }

    $event_type = self::ET_NOTHING;
    if (!empty($update->message->text)) {
      $event_type = $event_type | self::ET_TEXT;
    }
    if (is_object($update->message->audio)) {
      $event_type = $event_type | self::ET_AUDIO;
    }
    if (is_object($update->message->document)) {
      $event_type = $event_type | self::ET_DOCUMENT;
    }
    if (is_object($update->message->game)) {
      $event_type = $event_type | self::ET_GAME;
    }
    if (is_object($update->message->photo)) {
      $event_type = $event_type | self::ET_PHOTO;
    }
    if (is_object($update->message->sticker)) {
      $event_type = $event_type | self::ET_STICKER;
    }
    if (is_object($update->message->video)) {
      $event_type = $event_type | self::ET_VIDEO;
    }
    if (is_object($update->message->voice)) {
      $event_type = $event_type | self::ET_VOICE;
    }
    if (is_object($update->message->contact)) {
      $event_type = $event_type | self::ET_CONTACT;
    }
    if (is_object($update->message->location)) {
      $event_type = $event_type | self::ET_LOCATION;
    }
    if (is_object($update->message->venue)) {
      $event_type = $event_type | self::ET_VENUE;
    }
    if (is_object($update->message->new_chat_member)) {
      $event_type = $event_type | self::ET_NEW_CHAT_MEMBER;
    }
    if (is_object($update->message->left_chat_member)) {
      $event_type = $event_type | self::ET_LEFT_CHAT_MEMBER;
    }
    if (!empty($update->message->new_chat_title)) {
      $event_type = $event_type | self::ET_NEW_CHAT_TITLE;
    }
    if (is_object($update->message->new_chat_photo)) {
      $event_type = $event_type | self::ET_NEW_CHAT_PHOTO;
    }
    if ($update->message->delete_chat_photo) {
      $event_type = $event_type | self::ET_DELETE_CHAT_PHOTO;
    }
    if ($update->message->group_chat_created) {
      $event_type = $event_type | self::ET_GROUP_CHAT_CREATED;
    }
    if ($update->message->supergroup_chat_created) {
      $event_type = $event_type | self::ET_SUPERGROUP_CHAT_CREATED;
    }
    if ($update->message->channel_chat_created) {
      $event_type = $event_type | self::ET_CHANNEL_CHAT_CREATED;
    }
    if ($update->message->migrate_to_chat_id != 0) {
      $event_type = $event_type | self::ET_MIGRATE_TO_CHAT_ID;
    }
    if ($update->message->migrate_from_chat_id != 0) {
      $event_type = $event_type | self::ET_MIGRATE_FROM_CHAT_ID;
    }
    if (!is_null($update->message->pinned_message)) {
      $event_type = $event_type | self::ET_PINNED_MESSAGE;
    }

    return $event_type;
  }

  /**
   * Handles update with Message in it.
   *
   * @param \unreal4u\TelegramAPI\Telegram\Types\Update $update
   * @throws \Exception
   */
  protected function handleUpdateWithMessage(Update $update) {
    try {
      /** @var LoggerInterface $l */
      $l = $this->container->get('logger');

      // Find event type
      $event_type = $this->whatEventType($update);
      $l->debug('Event type: "{type}"', ['type' => $event_type]);

      // Execute registered event listeners
      $this->executeListeners($event_type, $update);

    } catch (\Exception $e) {
      try {
        $this->sendMessage($update->message->chat->id,
          'На сервере произошла ошибка, пожалуйста, сообщите системному администратору.');
      } catch (\Exception $passthrough) {
        // Do nothing
      }
      throw $e;
    }
  }

  /**
   * Executes event listeners.
   *
   * @param $event_type
   * @param \unreal4u\TelegramAPI\Telegram\Types\Update $update
   */
  protected function executeListeners($event_type, Update $update) {
    $l = $this->container->get('logger');

    // Array of listeners that will be executed
    $to_be_executed = [];
    foreach ($this->listeners as $item) {
      if ($item['event_type'] & $event_type) {
        $to_be_executed[$item['event_listener']] = TRUE;
      }
    }

    // To execute every listener only once
    /** @var EventListenerInterface $listener */
    foreach ($to_be_executed as $listener_class => $item) {
      $listener = new $listener_class();

      // Allow some debug info
      $l->debug('Executing listener "{listener_class}"',
        ['listener_class' => $listener_class]);

      // Execute listener object
      $listener->execute($this, $update);
    }
  }

  /**
   * Handles event with text
   *
   * @param \unreal4u\TelegramAPI\Telegram\Types\Update $update
   * @throws \Exception
   */
  /*protected function handleEventText(Update $update) {
    /** @var LoggerInterface $l
    $l = $this->container->get('logger');

    // Parse command "/start@BotName params"
    if (preg_match('/^\/([a-z_]+)@?([a-z_]*)\s*(.*)$/i', $update->message->text,
      $matches)) {

      if (isset($matches[1]) && ($command_name = $matches[1])) {
        $l->debug('Detected incoming command /{command_name}',
          ['command_name' => $command_name]);

        // Delete hook because command takes precedence
        // then execute command
        $this->deleteHook($update);
        $this->getBus()
          ->executeCommand($command_name, $update);

        return;
      }
    }
    $l->debug('No commands detected within incoming update');
  }*/

  /**
   * Executes hook if found.
   *
   * @param \unreal4u\TelegramAPI\Telegram\Types\Update $update
   */
  public function executeHook(Update $update) {
    // Check for hooks and execute if any found
    if ($hook = $this->getBus()
      ->getHooker()
      ->findHook($update)
    ) {
      $this->getBus()
        ->getHooker()
        ->executeHook($hook, $update)
        ->deleteHook($hook);
    }
  }

  /**
   * Deletes hook if found.
   *
   * @param \unreal4u\TelegramAPI\Telegram\Types\Update $update
   */
  public function deleteHook(Update $update) {
    // Find and delete the hook
    if ($hook = $this->getBus()
      ->getHooker()
      ->findHook($update)
    ) {
      $this->getBus()
        ->getHooker()
        ->deleteHook($hook);
    }
  }

  /**
   * @return \Kaula\TelegramBundle\Telegram\CommandBus
   */
  public function getBus(): CommandBus {
    return $this->bus;
  }

  /**
   * Returns container.
   *
   * @return \Symfony\Component\DependencyInjection\ContainerInterface
   */
  public function getContainer(): ContainerInterface {
    return $this->container;
  }

  /**
   * Adds event listener class to listeners collection.
   *
   * @param string $event_type
   * @param string $listener
   * @return Bot
   */
  public function addEventListener($event_type, $listener) {
    $implements = class_implements($listener);
    if (!isset($implements['Kaula\TelegramBundle\Telegram\Listener\EventListenerInterface'])) {
      throw new KaulaTelegramBundleException('Listener object should implement EventListenerInterface.');
    }

    $this->listeners[] = [
      'event_type'     => $event_type,
      'event_listener' => $listener,
    ];

    return $this;
  }

  /**
   * Use this method to send text messages. On success, the sent Message is
   * returned.
   *
   * @param string $chat_id Unique identifier for the target chat or username
   *   of the target channel (in the format @channelusername)
   * @param string $text Text of the message to be sent
   * @param string $parse_mode Send Markdown or HTML, if you want Telegram apps
   *   to show bold, italic, fixed-width text or inline URLs in your bot's
   *   message.
   * @param KeyboardMethods $reply_markup Additional interface options. A
   *   JSON-serialized object for an inline keyboard, custom reply keyboard,
   *   instructions to remove reply keyboard or to force a reply from the user.
   * @param bool $disable_web_page_preview Disables link previews for links in
   *   this message
   * @param bool $disable_notification Sends the message silently. iOS users
   *   will not receive a notification, Android users will receive a
   *   notification with no sound.
   * @param string $reply_to_message_id If the message is a reply, ID of the
   *   original message
   *
   * @return Message
   */
  public function sendMessage($chat_id, $text, $parse_mode = '', $reply_markup = NULL, $disable_web_page_preview = FALSE, $disable_notification = FALSE, $reply_to_message_id = NULL) {
    /** @var LoggerInterface $l */
    $l = $this->container->get('logger');

    $send_message = new SendMessage();
    $send_message->chat_id = $chat_id;
    $send_message->text = $text;
    $send_message->parse_mode = $parse_mode;
    $send_message->disable_web_page_preview = $disable_web_page_preview;
    $send_message->disable_notification = $disable_notification;
    $send_message->reply_to_message_id = $reply_to_message_id;
    $send_message->reply_markup = $reply_markup;

    // Get configuration
    $config = $this->container->getParameter('kaula_telegram');

    // Allow some debug info
    $l->debug('Bot is sending message',
      ['message' => print_r($send_message, TRUE)]);

    try {
      $tgLog = new TgLog($config['api_token'], $this->container->get('logger'));
      /** @var Message $message */
      $message = $tgLog->performApiRequest($send_message);

      return $message;
    } catch (ClientException $e) {
      throw $e;
    }
  }
}