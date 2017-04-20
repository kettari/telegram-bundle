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
use Kaula\TelegramBundle\Entity\Log;
use Kaula\TelegramBundle\Entity\Queue;

use Kaula\TelegramBundle\Entity\User;
use Kaula\TelegramBundle\Exception\KaulaTelegramBundleException;
use Kaula\TelegramBundle\Exception\ThrottleControlException;
use Kaula\TelegramBundle\Telegram\Command\HelpCommand;
use Kaula\TelegramBundle\Telegram\Command\ListRolesCommand;
use Kaula\TelegramBundle\Telegram\Command\PushCommand;
use Kaula\TelegramBundle\Telegram\Command\SettingsCommand;
use Kaula\TelegramBundle\Telegram\Command\StartCommand;
use Kaula\TelegramBundle\Telegram\Command\UserManCommand;
use Kaula\TelegramBundle\Telegram\Listener\GroupCreatedEvent;
use Kaula\TelegramBundle\Telegram\Listener\IdentityWatchdogEvent;
use Kaula\TelegramBundle\Telegram\Listener\LeftChatMemberEvent;
use Kaula\TelegramBundle\Telegram\Listener\JoinChatMemberEvent;
use Kaula\TelegramBundle\Telegram\Listener\EventListenerInterface;
use Kaula\TelegramBundle\Telegram\Listener\ExecuteCommandsEvent;
use Kaula\TelegramBundle\Telegram\Listener\MigrateFromChatIdEvent;
use Kaula\TelegramBundle\Telegram\Listener\MigrateToChatIdEvent;
use Psr\Log\LoggerInterface;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Stopwatch\Stopwatch;
use unreal4u\TelegramAPI\Abstracts\KeyboardMethods;
use unreal4u\TelegramAPI\Abstracts\TelegramMethods;
use unreal4u\TelegramAPI\Telegram\Methods\AnswerCallbackQuery;
use unreal4u\TelegramAPI\Telegram\Methods\EditMessageReplyMarkup;
use unreal4u\TelegramAPI\Telegram\Methods\EditMessageText;
use unreal4u\TelegramAPI\Telegram\Methods\SendChatAction;
use unreal4u\TelegramAPI\Telegram\Methods\SendMessage;
use unreal4u\TelegramAPI\Telegram\Types\Message;
use unreal4u\TelegramAPI\Telegram\Types\Update;
use unreal4u\TelegramAPI\TgLog;

class Bot
{

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

  // Special notification type to send private push
  const NOTIFICATION_PRIVATE = 'notification-private';

  /**
   * @var CommandBus
   */
  protected $bus;

  /**
   * @var ThrottleSingleton
   */
  protected $throttle_singleton;

  /**
   * Symfony container.
   *
   * @var ContainerInterface
   */
  protected $container;

  /**
   * User manager.
   *
   * @var UserHq
   */
  private $user_hq;

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
  public function __construct(ContainerInterface $container)
  {
    $this->container = $container;
    $this->bus = new CommandBus($this);
    $this->bus->registerCommand(StartCommand::class)
      ->registerCommand(SettingsCommand::class)
      ->registerCommand(HelpCommand::class)
      ->registerCommand(ListRolesCommand::class)
      ->registerCommand(PushCommand::class)
      ->registerCommand(UserManCommand::class);

    // Instantiate user headquarters
    $this->user_hq = new UserHq($container);

    // Throttle control
    $this->throttle_singleton = ThrottleSingleton::getInstance();

    // Add default listeners
    $this->addDefaultListeners();
  }

  /**
   * Adds default listeners.
   */
  protected function addDefaultListeners()
  {
    $this->addEventListener(self::ET_ANY, IdentityWatchdogEvent::class)
      ->addEventListener(self::ET_TEXT, ExecuteCommandsEvent::class)
      ->addEventListener(self::ET_NEW_CHAT_MEMBER, JoinChatMemberEvent::class)
      ->addEventListener(self::ET_LEFT_CHAT_MEMBER, LeftChatMemberEvent::class)
      ->addEventListener(
        self::ET_MIGRATE_TO_CHAT_ID,
        MigrateToChatIdEvent::class
      )
      ->addEventListener(
        self::ET_MIGRATE_FROM_CHAT_ID,
        MigrateFromChatIdEvent::class
      )
      ->addEventListener(self::ET_GROUP_CHAT_CREATED, GroupCreatedEvent::class);
  }

  /**
   * Handles update.
   *
   * @param \unreal4u\TelegramAPI\Telegram\Types\Update $update
   * @return void
   * @throws \InvalidArgumentException
   */
  public function handleUpdate(Update $update = null)
  {
    /** @var LoggerInterface $l */
    $l = $this->container->get('logger');

    // Scrap incoming data
    if (is_null($update)) {
      $update = $this->scrapIncomingData();
    }
    // Log incoming message
    $this->log($update);
    // Process it
    $update_type = $this->whatUpdateType($update);
    $this->getUserHq()
      ->stashUser($update_type, $update);

    // Allow some debug info
    $l->info(
      'Handling telegram update of type "{type}"',
      ['type' => $update_type, 'update' => print_r($update, true)]
    );

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
  protected function scrapIncomingData()
  {
    $update_data = json_decode(file_get_contents('php://input'), true);
    if (JSON_ERROR_NONE != json_last_error()) {
      throw new InvalidArgumentException('JSON error: '.json_last_error_msg());
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
  protected function whatUpdateType(Update $update)
  {
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
  protected function whatEventType(Update $update)
  {
    if (is_null($update->message)) {
      throw new KaulaTelegramBundleException(
        'Unable to tell event type: message object for this update is null.'
      );
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
  protected function handleUpdateWithMessage(Update $update)
  {
    try {
      /** @var LoggerInterface $l */
      $l = $this->container->get('logger');

      // Find event type
      $event_type = $this->whatEventType($update);
      $l->debug('Event type: "{type}"', ['type' => $event_type]);

      if (!$this->getUserHq()
        ->isUserBlocked()
      ) {
        // Execute registered event listeners
        $this->executeListeners($event_type, $update);
      } else {
        $this->sendMessage(
          $update->message->chat->id,
          'Извините, ваш аккаунт заблокирован.'
        );
      }

    } catch (\Exception $e) {
      $l->critical(
        'Exception while handling update with message: {error_message}',
        ['error_message' => $e->getMessage(), 'error_object' => $e]
      );
      try {
        $this->sendMessage(
          $update->message->chat->id,
          'На сервере произошла ошибка, пожалуйста, сообщите системному администратору.'
        );

        if ('dev' == $this->getContainer()
            ->getParameter("kernel.environment")
        ) {
          $this->sendMessage(
            $update->message->chat->id,
            $e->getMessage()
          );
        }
      } catch (\Exception $passthrough) {
        // Do nothing
      }
    }
  }


  /**
   * Executes event listeners.
   *
   * @param $event_type
   * @param \unreal4u\TelegramAPI\Telegram\Types\Update $update
   */
  protected function executeListeners($event_type, Update $update)
  {
    $l = $this->container->get('logger');

    // Array of listeners that will be executed
    $to_be_executed = [];
    foreach ($this->listeners as $item) {
      if ($item['event_type'] & $event_type) {
        $to_be_executed[$item['event_listener']] = true;
      }
    }

    // To execute every listener only once
    /** @var EventListenerInterface $listener */
    foreach ($to_be_executed as $listener_class => $item) {
      $listener = new $listener_class();

      // Allow some debug info
      $l->debug(
        'Executing listener "{listener_class}"',
        ['listener_class' => $listener_class]
      );

      // Execute listener object
      $listener->execute($this, $update);
    }
  }

  /**
   * Executes hook if found.
   *
   * @param \unreal4u\TelegramAPI\Telegram\Types\Update $update
   */
  public function executeHook(Update $update)
  {
    // Check for hooks and execute if any found
    /** @var \Kaula\TelegramBundle\Entity\Hook $hook */
    if ($hook = $this->getBus()
      ->getHooker()
      ->findHook($update)
    ) {

      if (!$this->getUserHq()
        ->isUserBlocked()
      ) {
        // User is active - execute & delete the hook
        $this->getBus()
          ->getHooker()
          ->executeHook($hook, $update)
          ->deleteHook($hook);
      } else {
        // User is blocked - just delete the hook
        $this->getBus()
          ->getHooker()
          ->deleteHook($hook);
      }

    }
  }

  /**
   * Deletes hook if found.
   *
   * @param \unreal4u\TelegramAPI\Telegram\Types\Update $update
   */
  public function deleteHook(Update $update)
  {
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
  public function getBus(): CommandBus
  {
    return $this->bus;
  }

  /**
   * @return ThrottleSingleton
   */
  public function getThrottleSingleton(): ThrottleSingleton
  {
    return $this->throttle_singleton;
  }

  /**
   * Returns container.
   *
   * @return \Symfony\Component\DependencyInjection\ContainerInterface
   */
  public function getContainer(): ContainerInterface
  {
    return $this->container;
  }

  /**
   * @return UserHq
   */
  public function getUserHq(): UserHq
  {
    return $this->user_hq;
  }

  /**
   * Adds event listener class to listeners collection.
   *
   * @param string $event_type
   * @param string $listener
   * @return Bot
   */
  public function addEventListener($event_type, $listener)
  {
    $implements = class_implements($listener);
    if (!isset($implements['Kaula\TelegramBundle\Telegram\Listener\EventListenerInterface'])) {
      throw new KaulaTelegramBundleException(
        'Listener object should implement EventListenerInterface.'
      );
    }

    $this->listeners[] = [
      'event_type'     => $event_type,
      'event_listener' => $listener,
    ];

    return $this;
  }

  /**
   * Performs actual API request.
   *
   * @param \unreal4u\TelegramAPI\Abstracts\TelegramMethods $method
   * @return \unreal4u\TelegramAPI\Abstracts\TelegramTypes
   */
  private function performRequest(TelegramMethods $method)
  {
    /** @var LoggerInterface $l */
    $l = $this->getContainer()
      ->get('logger');

    try {
      // Get configuration
      $config = $this->getContainer()
        ->getParameter('kaula_telegram');

      // Throttle control to avoid flood
      if ($this->getThrottleSingleton()
        ->wait()
      ) {
        $tgLog = new TgLog($config['api_token'], $l);
        $this->getThrottleSingleton()
          ->requestSent();

        // Log transaction
        $this->log($method);

        return $tgLog->performApiRequest($method);
      } else {
        throw new ThrottleControlException(
          'Throttle control exception: was unable to wait()'
        );
      }
    } catch (ClientException $e) {

      // User blocked the bot
      if (403 == $e->getCode()) {

        if (method_exists($method, 'chat_id')) {
          /** @noinspection PhpUndefinedFieldInspection */
          $chat_id = $method->chat_id;
        } else {
          $chat_id = '(undefined)';
        }

        $l->notice(
          'Bot is blocked in the chat {chat_id}',
          ['chat_id' => $chat_id]
        );
      } else {
        // Other errors
        throw $e;
      }
    }

    return null;
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
  public function sendMessage(
    $chat_id,
    $text,
    $parse_mode = null,
    $reply_markup = null,
    $disable_web_page_preview = false,
    $disable_notification = false,
    $reply_to_message_id = null
  ) {
    /** @var LoggerInterface $l */
    $l = $this->container->get('logger');

    $send_message = new SendMessage();
    $send_message->chat_id = $chat_id;
    $send_message->text = $text;
    $send_message->parse_mode = $parse_mode ?? '';
    $send_message->disable_web_page_preview = $disable_web_page_preview;
    $send_message->disable_notification = $disable_notification;
    $send_message->reply_to_message_id = $reply_to_message_id;
    $send_message->reply_markup = $reply_markup;

    // Allow some debug info
    $l->debug(
      'Bot is sending message',
      ['message' => print_r($send_message, true)]
    );

    /** @var Message $message */
    $message = $this->performRequest($send_message);

    return $message;
  }

  /**
   * Use this method to edit only the reply markup of messages sent by the bot
   * or via the bot (for inline bots). On success, if edited message is sent by
   * the bot, the edited Message is returned, otherwise True is returned.
   *
   * @param integer|string $chat_id Required if inline_message_id is not
   *   specified. Unique identifier for the target chat or username of the
   *   target channel (in the format @channelusername)
   * @param integer $message_id Required if inline_message_id is not specified.
   *   Identifier of the sent message
   * @param string $inline_message_id Required if chat_id and message_id are
   *   not specified. Identifier of the inline message
   * @param string $text Text of the message to be sent
   * @param string $parse_mode Send Markdown or HTML, if you want Telegram apps
   *   to show bold, italic, fixed-width text or inline URLs in your bot's
   *   message.
   * @param KeyboardMethods $reply_markup Additional interface options. A
   *   JSON-serialized object for an inline keyboard, custom reply keyboard,
   *   instructions to remove reply keyboard or to force a reply from the user.
   * @param bool $disable_web_page_preview Disables link previews for links in
   *   this message
   * @return \unreal4u\TelegramAPI\Telegram\Types\Message
   */
  public function editMessageText(
    $chat_id = null,
    $message_id = null,
    $inline_message_id = null,
    $text,
    $parse_mode = null,
    $reply_markup = null,
    $disable_web_page_preview = false
  ) {
    /** @var LoggerInterface $l */
    $l = $this->container->get('logger');

    $edit_message_text = new EditMessageText();
    $edit_message_text->chat_id = $chat_id;
    $edit_message_text->message_id = $message_id;
    $edit_message_text->inline_message_id = $inline_message_id;
    $edit_message_text->text = $text;
    $edit_message_text->parse_mode = $parse_mode;
    $edit_message_text->reply_markup = $reply_markup;
    $edit_message_text->disable_web_page_preview = $disable_web_page_preview;

    // Allow some debug info
    $l->debug(
      'Bot is editing message text',
      ['message' => print_r($edit_message_text, true)]
    );

    /** @var Message $message */
    $message = $this->performRequest($edit_message_text);

    return $message;
  }

  /**
   * Use this method to edit only the reply markup of messages sent by the bot
   * or via the bot (for inline bots). On success, if edited message is sent by
   * the bot, the edited Message is returned, otherwise True is returned.
   *
   * @param integer|string $chat_id Required if inline_message_id is not
   *   specified. Unique identifier for the target chat or username of the
   *   target channel (in the format @channelusername)
   * @param integer $message_id Required if inline_message_id is not specified.
   *   Identifier of the sent message
   * @param string $inline_message_id Required if chat_id and message_id are
   *   not specified. Identifier of the inline message
   * @param \unreal4u\TelegramAPI\Telegram\Types\Inline\Keyboard\Markup $reply_markup A
   *   JSON-serialized object for an inline keyboard.
   * @return \unreal4u\TelegramAPI\Telegram\Types\Message
   */
  public function editMessageReplyMarkup(
    $chat_id = null,
    $message_id = null,
    $inline_message_id = null,
    $reply_markup = null
  ) {
    /** @var LoggerInterface $l */
    $l = $this->container->get('logger');

    $edit_message_markup = new EditMessageReplyMarkup();
    $edit_message_markup->chat_id = $chat_id;
    $edit_message_markup->message_id = $message_id;
    $edit_message_markup->inline_message_id = $inline_message_id;
    $edit_message_markup->reply_markup = $reply_markup;

    // Allow some debug info
    $l->debug(
      'Bot is editing reply markup keyboard',
      ['message' => print_r($edit_message_markup, true)]
    );

    /** @var Message $message */
    $message = $this->performRequest($edit_message_markup);

    return $message;
  }

  /**
   * Use this method to send answers to callback queries sent from inline
   * keyboards. The answer will be displayed to the user as a notification at
   * the top of the chat screen or as an alert. On success, True is returned.
   *
   * @param string $callback_query_id Unique identifier for the query to be
   *   answered
   * @param string $text Text of the notification. If not specified, nothing
   *   will be shown to the user, 0-200 characters
   * @param bool $show_alert If true, an alert will be shown by the client
   *   instead of a notification at the top of the chat screen. Defaults to
   *   false.
   * @param string $url URL that will be opened by the user's client. If you
   *   have created a Game and accepted the conditions via @Botfather, specify
   *   the URL that opens your game – note that this will only work if the
   *   query comes from a callback_game button. Otherwise, you may use links
   *   like telegram.me/your_bot?start=XXXX that open your bot with a
   *   parameter.
   * @param int $cache_time The maximum amount of time in seconds that the
   *   result of the callback query may be cached client-side. Telegram apps
   *   will support caching starting in version 3.14. Defaults to 0.
   * @return bool
   */
  public function answerCallbackQuery(
    $callback_query_id,
    $text = null,
    $show_alert = false,
    $url = null,
    $cache_time = 0
  ) {
    /** @var LoggerInterface $l */
    $l = $this->container->get('logger');

    $answer_cb_query = new AnswerCallbackQuery();
    $answer_cb_query->callback_query_id = $callback_query_id;
    $answer_cb_query->text = $text;
    $answer_cb_query->show_alert = $show_alert;
    $answer_cb_query->url = $url;
    $answer_cb_query->cache_time = $cache_time;

    // Allow some debug info
    $l->debug(
      'Bot is answering callback query',
      ['message' => print_r($answer_cb_query, true)]
    );

    /** @var bool $result */
    $result = $this->performRequest($answer_cb_query);

    return $result;
  }

  /**
   * Use this method when you need to tell the user that something is happening
   * on the bot's side. The status is set for 5 seconds or less (when a message
   * arrives from your bot, Telegram clients clear its typing status).
   *
   * Example: The ImageBot needs some time to process a request and upload the
   * image. Instead of sending a text message along the lines of “Retrieving
   * image, please wait…”, the bot may use sendChatAction with action =
   * upload_photo. The user will see a “sending photo” status for the bot. We
   * only recommend using this method when a response from the bot will take a
   * noticeable amount of time to arrive.
   *
   * Objects defined as-is july 2016
   *
   * @see https://core.telegram.org/bots/api#sendchataction
   * @param string $chat_id Unique identifier for the target chat or username
   *   of the target channel (in the format @channelusername)
   * @param string $action Type of action to broadcast. Choose one, depending
   *   on what the user is about to receive: typing for text messages,
   *   upload_photo for photos, record_video or upload_video for videos,
   *   record_audio or upload_audio for audio files, upload_document for
   *   general files, find_location for location data.
   */
  public function sendAction($chat_id, $action = 'typing')
  {
    $send_chat_action = new SendChatAction();
    $send_chat_action->chat_id = $chat_id;
    $send_chat_action->action = $action;

    /** @var Message $message */
    $this->performRequest($send_chat_action);
  }

  /**
   * Push notification to users.
   *
   * @param string $notification Notification name. If $recipient is specified,
   *   this option is ignored.
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
   * @param \Kaula\TelegramBundle\Entity\User|null $recipient If specified,
   *   send notification only to this user.
   * @param string|null $chat_id Only if $recipient is specified: use this chat
   *   instead of private. If skipped, message is send privately
   */
  public function pushNotification(
    $notification,
    $text,
    $parse_mode = '',
    $reply_markup = null,
    $disable_web_page_preview = false,
    $disable_notification = false,
    User $recipient = null,
    $chat_id = null
  ) {
    $now = new \DateTime('now', new \DateTimeZone('UTC'));
    $d = $this->getContainer()
      ->get('doctrine');
    /** @var LoggerInterface $l */
    $l = $this->getContainer()
      ->get('logger');

    $l->debug(
      'Pushing notification "{notification}"',
      [
        'notification'     => $notification,
        'telegram_user_id' => !is_null($recipient) ? $recipient->getTelegramId(
        ) : '(all)',
        'chat_id'          => $chat_id,
      ]
    );

    $subscribers = $this->getEligibleSubscribers($notification, $recipient);
    $l->debug(
      'Subscribers to receive notification: {subscribers_count}',
      ['subscribers_count' => count($subscribers)]
    );

    /** @var \Kaula\TelegramBundle\Entity\User $user_item */
    foreach ($subscribers as $user_item) {
      if ($user_item->isBlocked()) {
        continue;
      }

      if (is_null(
        $chat = $d->getRepository('KaulaTelegramBundle:Chat')
          ->findOneBy(
            [
              'telegram_id' => is_null($chat_id) ? $user_item->getTelegramId(
              ) : $chat_id,
            ]
          )
      )) {
        throw new KaulaTelegramBundleException(
          sprintf(
            'Queue for push failed: unable to find chat for given user (telegram_user_id=%s)',
            $user_item->getTelegramId()
          )
        );
      }

      $queue = new Queue();
      $queue->setStatus('pending')
        ->setCreated($now)
        ->setChat($chat)
        ->setText($text)
        ->setParseMode($parse_mode)
        ->setReplyMarkup(
          !is_null($reply_markup) ? serialize($reply_markup) : null
        )
        ->setDisableWebPagePreview($disable_web_page_preview)
        ->setDisableNotification($disable_notification);
      $d->getManager()
        ->persist($queue);
    }
    $d->getManager()
      ->flush();
  }

  /**
   * Returns array of subscribers who will receive notification.
   *
   * @param string $notification Name of the notification
   * @param User $recipient Recipient who intended to receive notification
   * @return array|\Doctrine\Common\Collections\Collection
   */
  private function getEligibleSubscribers($notification, $recipient)
  {
    if (!is_null($recipient)) {
      return [$recipient];
    }

    // Load notification and users
    /** @var \Kaula\TelegramBundle\Entity\Notification $doctrine_notification */
    $doctrine_notification = $this->getContainer()
      ->get('doctrine')
      ->getRepository(
        'KaulaTelegramBundle:Notification'
      )
      ->findOneBy(['name' => $notification]);
    if (is_null($doctrine_notification)) {
      return [];
    }

    return $doctrine_notification->getUsers();
  }

  /**
   * Send part of queued messages.
   *
   * @param int $bump_size Count of items to send in this bump operation
   */
  public function bumpQueue($bump_size = 10)
  {
    $stopwatch = new Stopwatch();
    $stopwatch->start('bumpQueue');
    /** @var LoggerInterface $l */
    $l = $this->getContainer()
      ->get('logger');
    $items_count = 0;

    $queue = $this->getContainer()
      ->get('doctrine')
      ->getRepository(
        'KaulaTelegramBundle:Queue'
      )
      ->findBy(['status' => 'pending'], ['created' => 'ASC'], $bump_size);

    /** @var Queue $queue_item */
    foreach ($queue as $queue_item) {
      try {

        $this->sendMessage(
          $queue_item->getChat()
            ->getTelegramId(),
          $queue_item->getText(),
          $queue_item->getParseMode(),
          is_null(
            $queue_item->getReplyMarkup()
          ) ? null : unserialize($queue_item->getReplyMarkup()),
          $queue_item->getDisableWebPagePreview(),
          $queue_item->getDisableNotification()
        );
        $queue_item->setStatus('sent');

      } catch (\Exception $e) {

        $queue_item->setStatus('error')
          ->setExceptionMessage($e->getMessage());
        $l->warning(
          'Exception while sending queued message #{id}: {exception_message}',
          [
            'id'                => $queue_item->getId(),
            'exception_message' => $e->getMessage(),
          ]
        );

      } finally {

        $items_count++;
        $queue_item->setUpdated(new \DateTime('now', new \DateTimeZone('UTC')));

      }
    }
    $this->getContainer()
      ->get('doctrine')
      ->getManager()
      ->flush();

    $bump_event = $stopwatch->stop('bumpQueue');
    $elapsed = ($bump_event->getDuration() / 1000);
    $l->info(
      'Bumped queue: processed {items_count} items, elapsed {time_elapsed} seconds. Average items per second: {items_per_second}',
      [
        'items_count'      => $items_count,
        'time_elapsed'     => sprintf('%.2f', $elapsed),
        'items_per_second' => sprintf(
          '%.2f',
          ($elapsed > 0) ? ($items_count / $elapsed) : 0
        ),
      ]
    );
  }

  /**
   * Log transaction to the database.
   *
   * @param mixed $telegram_data
   */
  private function log($telegram_data)
  {
    $direction = 'undefined';
    $type = null;
    $chat_id = null;
    $content = null;

    // If $telegram_data instance of TelegramMethods, it is always outbound message
    if ($telegram_data instanceof TelegramMethods) {
      $direction = 'out';
      $type = get_class($telegram_data);
      $content = json_encode(
        $telegram_data->export(),
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
      );
    }
    // We can get Update only from Telegram, so it's always inbound
    if ($telegram_data instanceof Update) {
      $direction = 'in';
      $type = $this->whatUpdateType($telegram_data);
      $content = json_encode(
        get_object_vars($telegram_data),
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
      );
    }

    /**
     * INBOUND
     */
    if ($telegram_data instanceof Update) {
      if (!is_null($telegram_data->message)) {
        $chat_id = $telegram_data->message->chat->id;
      } elseif (!is_null($telegram_data->edited_message)) {
        $chat_id = $telegram_data->edited_message->chat->id;
      } elseif (!is_null($telegram_data->channel_post)) {
        $chat_id = $telegram_data->channel_post->chat->id;
      } elseif (!is_null($telegram_data->callback_query)) {
        if (!is_null($telegram_data->callback_query->message)) {
          $chat_id = $telegram_data->callback_query->message->chat->id;
        }
      }
    }

    /**
     * OUTBOUND
     **/
    if ($telegram_data instanceof SendMessage) {
      $chat_id = $telegram_data->chat_id;
    }
    if ($telegram_data instanceof SendChatAction) {
      $chat_id = $telegram_data->chat_id;
    }
    if ($telegram_data instanceof EditMessageReplyMarkup) {
      $chat_id = $telegram_data->chat_id;
    }

    $log = new Log();
    $log->setCreated(new \DateTime('now', new \DateTimeZone('UTC')))
      ->setDirection($direction)
      ->setType($type)
      ->setTelegramChatId($chat_id)
      ->setContent($content);

    $em = $this->getContainer()
      ->get('doctrine')
      ->getManager();
    $em->persist($log);
    $em->flush();
  }

}