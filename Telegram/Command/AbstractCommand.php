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

abstract class AbstractCommand {

  const PARSE_MODE_PLAIN = '';
  const PARSE_MODE_HTML = 'HTML';
  const PARSE_MODE_MARKDOWN = 'Markdown';

  /**
   * Command name.
   *
   * @var string
   */
  static public $name = NULL;

  /**
   * Command description.
   *
   * @var string
   */
  static public $description = NULL;

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
  static public $visible = TRUE;

  /**
   * @var CommandBus
   */
  protected $bus;

  /**
   * @var Update
   */
  protected $update;

  /**
   * Returns name of the command.
   *
   * @return string
   */
  static public function getName(): string  {
    return static::$name;
  }

  /**
   * Returns description of the command.
   *
   * @return string
   */
  static public function getDescription(): string {
    return static::$description;
  }

  /**
   * Returns supported patterns of the command.
   *
   * @return array
   */
  static public function getSupportedPatterns(): array {
    return static::$supported_patterns;
  }

  /**
   * Returns visibility flag of the command.
   *
   * @return bool
   */
  static public function isVisible(): bool {
    return static::$visible;
  }

  /**
   * AbstractCommand constructor.
   *
   * @param \Kaula\TelegramBundle\Telegram\CommandBus $bus
   * @param \unreal4u\TelegramAPI\Telegram\Types\Update $update
   */
  public function __construct(CommandBus $bus, Update $update) {
    $this->bus = $bus;
    $this->update = $update;
  }

  /**
   * Initialize command.
   *
   * @return AbstractCommand
   */
  abstract public function initialize();

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
  public function replyWithMessage($text, $parse_mode = '', $reply_markup = NULL, $disable_web_page_preview = FALSE, $disable_notification = FALSE, $reply_to_message_id = NULL) {
    $update = $this->getUpdate();

    return $this->getBus()
      ->getBot()
      ->sendMessage($update->message->chat->id, $text, $parse_mode,
        $reply_markup, $disable_web_page_preview, $disable_notification,
        $reply_to_message_id);
  }

  /**
   * @return \Kaula\TelegramBundle\Telegram\CommandBus
   */
  public function getBus(): CommandBus {
    return $this->bus;
  }

  /**
   * @return \unreal4u\TelegramAPI\Telegram\Types\Update
   */
  public function getUpdate(): Update {
    return $this->update;
  }

}