<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram\Command;

use unreal4u\TelegramAPI\Abstracts\KeyboardMethods;
use unreal4u\TelegramAPI\Telegram\Types\Message;
use unreal4u\TelegramAPI\Telegram\Types\Update;

abstract class AbstractCommand implements TelegramCommandInterface
{
  /**
   * Command name.
   *
   * @var string
   */
  static public $name = '';

  /**
   * Command description.
   *
   * @var string
   */
  static public $description = '';

  /**
   * Array of REGEX patterns this command supports.
   *
   * @var array
   */
  static public $supportedPatterns = [];

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
  static public $requiredPermissions = [];

  /**
   * Notifications declared in this command.
   *
   * @var array
   */
  static public $declaredNotifications = [];

  /**
   * @var Update
   */
  protected $update;

  /**
   * @var string
   */
  protected $commandParameter = '';

  /**
   * AbstractCommand constructor.
   *
   * @param \unreal4u\TelegramAPI\Telegram\Types\Update $update
   */
  public function __construct(Update $update)
  {
    $this->update = $update;
  }

  /**
   * {@inheritdoc}
   */
  static public function getName(): string
  {
    return static::$name;
  }

  /**
   * {@inheritdoc}
   */
  static public function getDescription(): string
  {
    return static::$description;
  }

  /**
   * {@inheritdoc}
   */
  static public function getSupportedPatterns(): array
  {
    return static::$supportedPatterns;
  }

  /**
   * {@inheritdoc}
   */
  static public function isVisible(): bool
  {
    return static::$visible;
  }

  /**
   * {@inheritdoc}
   */
  static public function getRequiredPermissions(): array
  {
    return static::$requiredPermissions;
  }

  /**
   * {@inheritdoc}
   */
  static public function getDeclaredNotifications(): array
  {
    return static::$declaredNotifications;
  }

  /**
   * {@inheritdoc}
   */
  public function initialize(string $commandParameter): TelegramCommandInterface
  {
    $this->commandParameter = $commandParameter;

    return $this;
  }

  /**
   * Executes command.
   *
   * @return void
   */
  abstract public function execute();

  /**
   * Replies with a long text message to the user. Splits message by PHP_EOL if
   * message exceeds maximum allowed by Telegram (4096 Unicode bytes).
   *
   * @param string $text Text of the message to be sent
   * @param string $parseMode Send Markdown or HTML, if you want Telegram apps
   *   to show bold, italic, fixed-width text or inline URLs in your bot's
   *   message.
   * @param KeyboardMethods $replyMarkup Additional interface options. A
   *   JSON-serialized object for an inline keyboard, custom reply keyboard,
   *   instructions to remove reply keyboard or to force a reply from the user.
   * @param bool $disableWebPagePreview Disables link previews for links in
   *   this message
   * @param bool $disableNotification Sends the message silently. iOS users
   *   will not receive a notification, Android users will receive a
   *   notification with no sound.
   * @param string $replyToMessageId If the message is a reply, ID of the
   *   original message
   */
  public function replyWithLongMessage(
    $text,
    $parseMode = null,
    $replyMarkup = null,
    $disableWebPagePreview = false,
    $disableNotification = false,
    $replyToMessageId = null
  ) {
    // Split the message into lines and implode again respecting max length
    $lines = explode(PHP_EOL, $text);
    $nextMessage = '';
    $messages = [];
    foreach ($lines as $oneLine) {
      if (mb_strlen($nextMessage.$oneLine) > 4095) {
        $messages[] = mb_substr($nextMessage.$oneLine, 0, 4096);
        $nextMessage = mb_substr($nextMessage.$oneLine, 4096, 4095).PHP_EOL;
      } else {
        $nextMessage .= $oneLine.PHP_EOL;
      }
    }
    $messages[] = $nextMessage;

    // Send all messages
    foreach ($messages as $oneMessage) {
      $this->replyWithMessage(
        $oneMessage,
        $parseMode,
        $replyMarkup,
        $disableWebPagePreview,
        $disableNotification,
        $replyToMessageId
      );
    }
  }

  /**
   * Replies with a text message to the user.
   *
   * @param string $text Text of the message to be sent
   * @param string $parseMode Send Markdown or HTML, if you want Telegram apps
   *   to show bold, italic, fixed-width text or inline URLs in your bot's
   *   message.
   * @param KeyboardMethods $replyMarkup Additional interface options. A
   *   JSON-serialized object for an inline keyboard, custom reply keyboard,
   *   instructions to remove reply keyboard or to force a reply from the user.
   * @param bool $disableWebPagePreview Disables link previews for links in
   *   this message
   * @param bool $disableNotification Sends the message silently. iOS users
   *   will not receive a notification, Android users will receive a
   *   notification with no sound.
   * @param string $replyToMessageId If the message is a reply, ID of the
   *   original message
   *
   * @return Message
   */
  public function replyWithMessage(
    $text,
    $parseMode = null,
    $replyMarkup = null,
    $disableWebPagePreview = false,
    $disableNotification = false,
    $replyToMessageId = null
  ) {
    $update = $this->getUpdate();

    return $this->getBus()
      ->getBot()
      ->sendMessage(
        $update->message->chat->id,
        $text,
        $parseMode,
        $replyMarkup,
        $disableWebPagePreview,
        $disableNotification,
        $replyToMessageId
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
  public function replyWithAction($action = self::ACTION_TYPING)
  {
    $update = $this->getUpdate();
    $this->getBus()
      ->getBot()
      ->sendAction($update->message->chat->id, $action);
  }


  /**
   * Returns text if we have some non-empty text in the message object.
   *
   * @return string
   */
  protected function getText(): string
  {
    return $this->hasText() ? $this->update->message->text : '';
  }

  /**
   * Returns true if we have some non-empty text in the message object.
   *
   * @return bool
   */
  protected function hasText(): bool
  {
    return !is_null($this->update->message) &&
      !empty($this->update->message->text);
  }

  /**
   * {@inheritdoc}
   */
  public function getCommandParameter(): string
  {
    return $this->commandParameter;
  }
}