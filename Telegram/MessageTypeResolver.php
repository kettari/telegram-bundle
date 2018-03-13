<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram;

use unreal4u\TelegramAPI\Telegram\Types\Message;

class MessageTypeResolver
{
  // Event types
  const MT_NOTHING = 0;
  const MT_TEXT = 1;
  const MT_AUDIO = 2;
  const MT_DOCUMENT = 4;
  const MT_GAME = 8;
  const MT_PHOTO = 16;
  const MT_STICKER = 32;
  const MT_VIDEO = 64;
  const MT_VOICE = 128;
  const MT_CONTACT = 256;
  const MT_LOCATION = 512;
  const MT_VENUE = 1024;
  const MT_NEW_CHAT_MEMBER = 2048;
  const MT_LEFT_CHAT_MEMBER = 4096;
  const MT_NEW_CHAT_TITLE = 8192;
  const MT_NEW_CHAT_PHOTO = 16384;
  const MT_DELETE_CHAT_PHOTO = 32768;
  const MT_GROUP_CHAT_CREATED = 65536;
  const MT_SUPERGROUP_CHAT_CREATED = 131072;
  const MT_CHANNEL_CHAT_CREATED = 262144;
  const MT_MIGRATE_TO_CHAT_ID = 524288;
  const MT_MIGRATE_FROM_CHAT_ID = 1048576;
  const MT_PINNED_MESSAGE = 2097152;
  const MT_NEW_CHAT_MEMBERS_MANY = 4194304;
  const MT_SUCCESSFUL_PAYMENT = 8388608;
  const MT_INVOICE = 16777216;
  const MT_VIDEO_NOTE = 33554432;
  const MT_ANY = 67108863;

  /**
   * Message type titles.
   *
   * @var array
   */
  private static $mtCaptions = [
    self::MT_TEXT                    => 'MT_TEXT',
    self::MT_AUDIO                   => 'MT_AUDIO',
    self::MT_DOCUMENT                => 'MT_DOCUMENT',
    self::MT_GAME                    => 'MT_GAME',
    self::MT_PHOTO                   => 'MT_PHOTO',
    self::MT_STICKER                 => 'MT_STICKER',
    self::MT_VIDEO                   => 'MT_VIDEO',
    self::MT_VOICE                   => 'MT_VOICE',
    self::MT_CONTACT                 => 'MT_CONTACT',
    self::MT_LOCATION                => 'MT_LOCATION',
    self::MT_VENUE                   => 'MT_VENUE',
    self::MT_NEW_CHAT_MEMBER         => 'MT_NEW_CHAT_MEMBER',
    self::MT_LEFT_CHAT_MEMBER        => 'MT_LEFT_CHAT_MEMBER',
    self::MT_NEW_CHAT_TITLE          => 'MT_NEW_CHAT_TITLE',
    self::MT_NEW_CHAT_PHOTO          => 'MT_NEW_CHAT_PHOTO',
    self::MT_DELETE_CHAT_PHOTO       => 'MT_DELETE_CHAT_PHOTO',
    self::MT_GROUP_CHAT_CREATED      => 'MT_GROUP_CHAT_CREATED',
    self::MT_SUPERGROUP_CHAT_CREATED => 'MT_SUPERGROUP_CHAT_CREATED',
    self::MT_CHANNEL_CHAT_CREATED    => 'MT_CHANNEL_CHAT_CREATED',
    self::MT_MIGRATE_TO_CHAT_ID      => 'MT_MIGRATE_TO_CHAT_ID',
    self::MT_MIGRATE_FROM_CHAT_ID    => 'MT_MIGRATE_FROM_CHAT_ID',
    self::MT_PINNED_MESSAGE          => 'MT_PINNED_MESSAGE',
    self::MT_NEW_CHAT_MEMBERS_MANY   => 'MT_NEW_CHAT_MEMBERS_MANY',
    self::MT_SUCCESSFUL_PAYMENT      => 'MT_SUCCESSFUL_PAYMENT',
    self::MT_INVOICE                 => 'MT_INVOICE',
    self::MT_VIDEO_NOTE              => 'MT_VIDEO_NOTE',
  ];


  /**
   * Returns type of the message.
   *
   * @param Message $message
   * @return integer
   */
  public static function getMessageType(Message $message)
  {
    $messageType = self::MT_NOTHING;
    if (!empty($message->text)) {
      $messageType = $messageType | self::MT_TEXT;
    }
    if (!empty($message->audio)) {
      $messageType = $messageType | self::MT_AUDIO;
    }
    if (!empty($message->document)) {
      $messageType = $messageType | self::MT_DOCUMENT;
    }
    if (!empty($message->game)) {
      $messageType = $messageType | self::MT_GAME;
    }
    if (!empty($message->photo)) {
      $messageType = $messageType | self::MT_PHOTO;
    }
    if (!empty($message->sticker)) {
      $messageType = $messageType | self::MT_STICKER;
    }
    if (!empty($message->video)) {
      $messageType = $messageType | self::MT_VIDEO;
    }
    if (!empty($message->voice)) {
      $messageType = $messageType | self::MT_VOICE;
    }
    if (!empty($message->contact)) {
      $messageType = $messageType | self::MT_CONTACT;
    }
    if (!empty($message->location)) {
      $messageType = $messageType | self::MT_LOCATION;
    }
    if (!empty($message->venue)) {
      $messageType = $messageType | self::MT_VENUE;
    }
    if (!empty($message->new_chat_member)) {
      $messageType = $messageType | self::MT_NEW_CHAT_MEMBER;
    }
    if (is_array($message->new_chat_members) &&
      (count($message->new_chat_members) > 0)) {
      $messageType = $messageType | self::MT_NEW_CHAT_MEMBERS_MANY;
    }
    if (!empty($message->left_chat_member)) {
      $messageType = $messageType | self::MT_LEFT_CHAT_MEMBER;
    }
    if (!empty($message->new_chat_title)) {
      $messageType = $messageType | self::MT_NEW_CHAT_TITLE;
    }
    if (!empty($message->new_chat_photo)) {
      $messageType = $messageType | self::MT_NEW_CHAT_PHOTO;
    }
    if ($message->delete_chat_photo) {
      $messageType = $messageType | self::MT_DELETE_CHAT_PHOTO;
    }
    if ($message->group_chat_created) {
      $messageType = $messageType | self::MT_GROUP_CHAT_CREATED;
    }
    if ($message->supergroup_chat_created) {
      $messageType = $messageType | self::MT_SUPERGROUP_CHAT_CREATED;
    }
    if ($message->channel_chat_created) {
      $messageType = $messageType | self::MT_CHANNEL_CHAT_CREATED;
    }
    if (0 != $message->migrate_to_chat_id) {
      $messageType = $messageType | self::MT_MIGRATE_TO_CHAT_ID;
    }
    if (0 != $message->migrate_from_chat_id) {
      $messageType = $messageType | self::MT_MIGRATE_FROM_CHAT_ID;
    }
    if (!empty($message->pinned_message)) {
      $messageType = $messageType | self::MT_PINNED_MESSAGE;
    }
    if (!empty($message->successful_payment)) {
      $messageType = $messageType | self::MT_SUCCESSFUL_PAYMENT;
    }
    if (!empty($message->invoice)) {
      $messageType = $messageType | self::MT_INVOICE;
    }
    if (!empty($message->video_note)) {
      $messageType = $messageType | self::MT_VIDEO_NOTE;
    }

    return $messageType;
  }

  /**
   * Returns title of the message type.
   *
   * @param int $messageType
   * @return string
   */
  public static function getMessageTypeTitle($messageType): string
  {
    $types = [];
    foreach (self::$mtCaptions as $key => $caption) {
      if ($key & $messageType) {
        $types[] = $caption;
      }
    }

    return (count($types) > 0) ? implode(
      ', ',
      $types
    ) : 'MT_UNKNOWN';
  }
}