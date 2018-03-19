<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram\Command;


use Kettari\TelegramBundle\Telegram\Communicator;
use unreal4u\TelegramAPI\Abstracts\KeyboardMethods;
use unreal4u\TelegramAPI\Telegram\Types\Message;
use unreal4u\TelegramAPI\Telegram\Types\Update;

trait ReplyWithTrait
{
  /**
   * Replies with a long text message to the user. Splits message by PHP_EOL if
   * message exceeds maximum allowed by Telegram (4096 Unicode bytes).
   *
   * @param \unreal4u\TelegramAPI\Telegram\Types\Update $update
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
   * @return null|Message
   */
  protected function replyWithMessage(
    Update $update,
    string $text,
    $parseMode = Communicator::PARSE_MODE_PLAIN,
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
      /** @noinspection PhpUndefinedFieldInspection */
      /** @noinspection PhpUndefinedMethodInspection */
      return $this->comm
        ->sendMessage(
          $update->message->chat->id,
          $oneMessage,
          $parseMode,
          $replyMarkup,
          $disableWebPagePreview,
          $disableNotification,
          $replyToMessageId
        );
    }

    return null;
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
   * @param \unreal4u\TelegramAPI\Telegram\Types\Update $update
   * @param string $action Type of action to broadcast. Choose one, depending
   *   on what the user is about to receive: typing for text messages,
   *   upload_photo for photos, record_video or upload_video for videos,
   *   record_audio or upload_audio for audio files, upload_document for
   *   general files, find_location for location data.
   */
  protected function replyWithAction(Update $update, $action = Communicator::ACTION_TYPING)
  {
    /** @noinspection PhpUndefinedFieldInspection */
    /** @noinspection PhpUndefinedMethodInspection */
    $this->bus->getCommunicator()
      ->sendAction($update->message->chat->id, $action);
  }
}