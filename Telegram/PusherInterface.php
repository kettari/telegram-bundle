<?php

namespace Kettari\TelegramBundle\Telegram;


use Kettari\TelegramBundle\Entity\User;
use unreal4u\TelegramAPI\Abstracts\KeyboardMethods;

interface PusherInterface
{
  /**
   * Push notification to users.
   *
   * @param string $notification Notification name. If $recipient is specified,
   *   this option is ignored.
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
   * @param \Kettari\TelegramBundle\Entity\User|null $recipient If specified,
   *   send notification only to this user.
   * @param string|null $chatId Only if $recipient is specified: use this chat
   *   instead of private. If skipped, message is send privately
   */
  public function pushNotification(
    string $notification,
    string $text,
    string $parseMode = '',
    $replyMarkup = null,
    $disableWebPagePreview = false,
    $disableNotification = false,
    User $recipient = null,
    $chatId = null
  );

  /**
   * Sends part of queued messages.
   */
  public function bumpQueue();
}