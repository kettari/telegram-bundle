<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram\Event;


use RuntimeException;
use unreal4u\TelegramAPI\Telegram\Types\Update;

class ChatDeletePhotoEvent extends AbstractMessageEvent
{
  const NAME = 'telegram.chat.delete_photo';

  /**
   * ChatDeletePhotoEvent constructor.
   *
   * @param Update $update
   */
  public function __construct(Update $update)
  {
    parent::__construct($update);
    if (empty($update->message->delete_chat_photo)) {
      throw new RuntimeException(
        'Chat delete photo of the Message can\'t be empty for the ChatDeletePhotoEvent.'
      );
    }
  }
}