<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram\Event;


use RuntimeException;
use unreal4u\TelegramAPI\Telegram\Types\Custom\PhotoSizeArray;
use unreal4u\TelegramAPI\Telegram\Types\Update;

class ChatNewPhotoEvent extends AbstractMessageEvent
{
  const NAME = 'telegram.chat.new_photo';

  /**
   * ChatNewPhotoEvent constructor.
   *
   * @param Update $update
   */
  public function __construct(Update $update)
  {
    parent::__construct($update);
    if (empty($update->message->new_chat_photo)) {
      throw new RuntimeException(
        'Chat photo of the Message can\'t be empty for the ChatNewPhotoEvent.'
      );
    }
  }

  /**
   * @return PhotoSizeArray
   */
  public function getChatPhotoSizes(): PhotoSizeArray
  {
    return $this->getMessage()->new_chat_photo;
  }

}