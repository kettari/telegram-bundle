<?php
/**
 * Created by PhpStorm.
 * User: ant
 * Date: 16.03.2017
 * Time: 17:46
 */

namespace Kaula\TelegramBundle\Telegram;


use GuzzleHttp\Exception\ClientException;

use Kaula\TelegramBundle\Telegram\Command\HelpCommand;
use Kaula\TelegramBundle\Telegram\Command\StartCommand;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use unreal4u\TelegramAPI\Abstracts\KeyboardMethods;
use unreal4u\TelegramAPI\Telegram\Methods\SendMessage;
use unreal4u\TelegramAPI\Telegram\Types\Message;
use unreal4u\TelegramAPI\Telegram\Types\Update;
use unreal4u\TelegramAPI\TgLog;

class Bot {

  /**
   * @var CommandBus
   */
  protected $bus;

  /**
   * @var ContainerInterface
   */
  protected $container;

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
  }

  /**
   * Handles update.
   *
   * @param \unreal4u\TelegramAPI\Telegram\Types\Update $update
   * @return bool Returns TRUE if appropriate command was found.
   */
  public function handleUpdate(Update $update = NULL) {
    /** @var LoggerInterface $l */
    $l = $this->container->get('logger');

    if (is_null($update)) {
      $update_data = json_decode(file_get_contents('php://input'), TRUE);
      $update = new Update($update_data);
    }

    // Allow some debug info
    $l->debug('Bot is handling telegram update',
      ['update' => print_r($update, TRUE)]);

    // If there is a Message object, analyze it
    if (!is_null($update->message)) {

      // Parse command "/start@BotName params"
      if (preg_match('/^\/([a-z_]+)@?([a-z_]*)\s*(.*)$/i',
        $update->message->text, $matches)) {
        if (isset($matches[1]) && ($command_name = $matches[1])) {
          $l->debug('Detected incoming command /{command_name}',
            ['command_name' => $command_name]);

          return $this->getBus()
            ->executeCommand($command_name, $update);
        }
      }
      $l->debug('No commands detected within incoming update');

      // Check for hooks
      if ($hook = $this->getBus()
        ->findHook($update)
      ) {
        $this->getBus()
          ->executeHook($hook, $update)
          ->deleteHook($hook);
      }

    }


    return FALSE;
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