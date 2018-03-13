<?php

namespace Kettari\TelegramBundle\Telegram;


use unreal4u\TelegramAPI\Abstracts\KeyboardMethods;
use unreal4u\TelegramAPI\Abstracts\TelegramMethods;
use unreal4u\TelegramAPI\Telegram\Types\Custom\InputFile;
use unreal4u\TelegramAPI\Telegram\Types\Message;

interface CommunicatorInterface
{
  /**
   * Use this method to send text messages. On success, the sent Message is
   * returned.
   *
   * @param int $chatId Unique identifier for the target chat or username
   *   of the target channel (in the format @channelusername)
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
   * @return Message|null
   */
  public function sendMessage(
    int $chatId,
    string $text,
    string $parseMode,
    $replyMarkup = null,
    $disableWebPagePreview = false,
    $disableNotification = false,
    $replyToMessageId = null
  );

  /**
   * Use this method to send photos. On success, the sent Message is
   * returned.
   *
   * @param int $chatId Unique identifier for the target chat or username
   *   of the target channel (in the format @channelusername)
   * @param string|\unreal4u\TelegramAPI\Telegram\Types\Custom\InputFile $inputFile Photo
   *   to send. Pass a file_id as String to send a photo that exists on the
   *   Telegram servers (recommended), pass an HTTP URL as a String for
   *   Telegram to get a photo from the Internet, or upload a new photo using
   *   the InputFile class.
   * @param string $caption Photo caption (may also be used when resending
   *   photos by file_id), 0-200 characters
   * @param KeyboardMethods $replyMarkup Additional interface options. A
   *   JSON-serialized object for an inline keyboard, custom reply keyboard,
   *   instructions to remove reply keyboard or to force a reply from the user.
   * @param bool $disableNotification Sends the message silently. iOS users
   *   will not receive a notification, Android users will receive a
   *   notification with no sound.
   * @param string $replyToMessageId If the message is a reply, ID of the
   *   original message
   *
   * @return Message|null
   */
  public function sendPhoto(
    int $chatId,
    InputFile $inputFile,
    $caption = null,
    $replyMarkup = null,
    $disableNotification = false,
    $replyToMessageId = null
  );

  /**
   * Use this method to edit only the reply markup of messages sent by the bot
   * or via the bot (for inline bots). On success, if edited message is sent by
   * the bot, the edited Message is returned, otherwise True is returned.
   *
   * @param integer $chatId Required if inline_message_id is not
   *   specified. Unique identifier for the target chat or username of the
   *   target channel (in the format @channelusername)
   * @param integer $messageId Required if inline_message_id is not specified.
   *   Identifier of the sent message
   * @param string $inlineMessageId Required if chat_id and message_id are
   *   not specified. Identifier of the inline message
   * @param string $text Text of the message to be sent
   * @param string $parseMode Send Markdown or HTML, if you want Telegram apps
   *   to show bold, italic, fixed-width text or inline URLs in your bot's
   *   message.
   * @param KeyboardMethods $replyMarkup Additional interface options. A
   *   JSON-serialized object for an inline keyboard, custom reply keyboard,
   *   instructions to remove reply keyboard or to force a reply from the user.
   * @param bool $disableWebPagePreview Disables link previews for links in
   *   this message
   * @return Message
   */
  public function editMessageText(
    $chatId = null,
    $messageId = null,
    $inlineMessageId = null,
    string $text,
    $parseMode = '',
    $replyMarkup = null,
    $disableWebPagePreview = false
  );

  /**
   * Use this method to edit only the reply markup of messages sent by the bot
   * or via the bot (for inline bots). On success, if edited message is sent by
   * the bot, the edited Message is returned, otherwise True is returned.
   *
   * @param integer $chatId Required if inline_message_id is not
   *   specified. Unique identifier for the target chat or username of the
   *   target channel (in the format @channelusername)
   * @param integer $messageId Required if inline_message_id is not specified.
   *   Identifier of the sent message
   * @param string $inlineMessageId Required if chat_id and message_id are
   *   not specified. Identifier of the inline message
   * @param \unreal4u\TelegramAPI\Telegram\Types\Inline\Keyboard\Markup $replyMarkup A
   *   JSON-serialized object for an inline keyboard.
   * @return Message
   */
  public function editMessageReplyMarkup(
    $chatId = null,
    $messageId = null,
    $inlineMessageId = null,
    $replyMarkup = null
  );

  /**
   * Use this method to send answers to callback queries sent from inline
   * keyboards. The answer will be displayed to the user as a notification at
   * the top of the chat screen or as an alert. On success, True is returned.
   *
   * @param string $callbackQueryId Unique identifier for the query to be
   *   answered
   * @param string $text Text of the notification. If not specified, nothing
   *   will be shown to the user, 0-200 characters
   * @param bool $showAlert If true, an alert will be shown by the client
   *   instead of a notification at the top of the chat screen. Defaults to
   *   false.
   * @param string $url URL that will be opened by the user's client. If you
   *   have created a Game and accepted the conditions via @Botfather, specify
   *   the URL that opens your game – note that this will only work if the
   *   query comes from a callback_game button. Otherwise, you may use links
   *   like telegram.me/your_bot?start=XXXX that open your bot with a
   *   parameter.
   * @param int $cacheTime The maximum amount of time in seconds that the
   *   result of the callback query may be cached client-side. Telegram apps
   *   will support caching starting in version 3.14. Defaults to 0.
   * @return bool
   */
  public function answerCallbackQuery(
    string $callbackQueryId,
    $text = null,
    $showAlert = false,
    $url = null,
    $cacheTime = 0
  );

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
   * @param int $chatId Unique identifier for the target chat or username
   *   of the target channel (in the format @channelusername)
   * @param string $action Type of action to broadcast. Choose one, depending
   *   on what the user is about to receive: typing for text messages,
   *   upload_photo for photos, record_video or upload_video for videos,
   *   record_audio or upload_audio for audio files, upload_document for
   *   general files, find_location for location data.
   */
  public function sendAction(int $chatId, string $action);

  /**
   * Returns true if communicator has unsent method.
   *
   * @return bool
   */
  public function isMethodDeferred(): bool;

  /**
   * Returns unsent Telegram method or null.
   *
   * @return TelegramMethods|null
   */
  public function getDeferredTelegramMethod();
}