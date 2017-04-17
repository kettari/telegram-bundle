<?php
/**
 * Created by PhpStorm.
 * User: ant
 * Date: 16.03.2017
 * Time: 18:15
 */

namespace Kaula\TelegramBundle\Telegram\Command;


use Kaula\TelegramBundle\Telegram\CommandBus;
use unreal4u\TelegramAPI\Abstracts\KeyboardMethods;
use unreal4u\TelegramAPI\Telegram\Types\Message;
use unreal4u\TelegramAPI\Telegram\Types\Update;

abstract class AbstractCommand
{

  const PARSE_MODE_PLAIN = '';
  const PARSE_MODE_HTML = 'HTML';
  const PARSE_MODE_MARKDOWN = 'Markdown';

  /**
   * Command name.
   *
   * @var string
   */
  static public $name = null;

  /**
   * Command description.
   *
   * @var string
   */
  static public $description = null;

  /**
   * Array of REGEX patterns this command supports.
   *
   * @var array
   */
  static public $supported_patterns = [];

  /**
   * If this command is showed in /help?
   *
   * @var bool
   */
  static public $visible = true;

  /**
   * Permissions required to execute this command.
   *
   * @var array
   */
  static public $required_permissions = [];

  /**
   * @var CommandBus
   */
  private $bus;

  /**
   * @var Update
   */
  private $update;

  /**
   * @var string
   */
  private $parameter = '';

  /**
   * Returns name of the command.
   *
   * @return string
   */
  static public function getName(): string
  {
    return static::$name;
  }

  /**
   * Returns description of the command.
   *
   * @return string
   */
  static public function getDescription(): string
  {
    return static::$description;
  }

  /**
   * Returns supported patterns of the command.
   *
   * @return array
   */
  static public function getSupportedPatterns(): array
  {
    return static::$supported_patterns;
  }

  /**
   * Returns visibility flag of the command.
   *
   * @return bool
   */
  static public function isVisible(): bool
  {
    return static::$visible;
  }

  /**
   * Returns required permissions to execute the command.
   *
   * @return array
   */
  static public function getRequiredPermissions(): array
  {
    return static::$required_permissions;
  }

  /**
   * AbstractCommand constructor.
   *
   * @param \Kaula\TelegramBundle\Telegram\CommandBus $bus
   * @param \unreal4u\TelegramAPI\Telegram\Types\Update $update
   */
  public function __construct(CommandBus $bus, Update $update)
  {
    $this->bus = $bus;
    $this->update = $update;
  }

  /**
   * Initialize command.
   *
   * @return AbstractCommand
   */
  public function initialize()
  {
    // Parse command "/start@BotName params"
    if (preg_match(
      '/^\/[a-z_]+@?[a-z_]*\s*(.*)$/i',
      $this->getUpdate()->message->text,
      $matches
    )) {
      if (isset($matches[1])) {
        $this->parameter = $matches[1];
      }
    }

    return $this;
  }

  /**
   * Executes command.
   *
   * @return void
   */
  abstract public function execute();

  /**
   * Replies with a text message to the user.
   *
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
  public function replyWithMessage(
    $text,
    $parse_mode = null,
    $reply_markup = null,
    $disable_web_page_preview = false,
    $disable_notification = false,
    $reply_to_message_id = null
  ) {
    $update = $this->getUpdate();

    return $this->getBus()
      ->getBot()
      ->sendMessage(
        $update->message->chat->id,
        $text,
        $parse_mode,
        $reply_markup,
        $disable_web_page_preview,
        $disable_notification,
        $reply_to_message_id
      );
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
   * @param string $action Type of action to broadcast. Choose one, depending
   *   on what the user is about to receive: typing for text messages,
   *   upload_photo for photos, record_video or upload_video for videos,
   *   record_audio or upload_audio for audio files, upload_document for
   *   general files, find_location for location data.
   */
  public function replyWithAction($action = 'typing')
  {
    $update = $this->getUpdate();
    $this->getBus()
      ->getBot()
      ->sendAction($update->message->chat->id, $action);
  }

  /**
   * @return \Kaula\TelegramBundle\Telegram\CommandBus
   */
  public function getBus(): CommandBus
  {
    return $this->bus;
  }

  /**
   * @return \unreal4u\TelegramAPI\Telegram\Types\Update
   */
  public function getUpdate(): Update
  {
    return $this->update;
  }

  /**
   * @return string
   */
  public function getParameter(): string
  {
    return $this->parameter;
  }

}