<?php
/**
 * Created by PhpStorm.
 * User: ant
 * Date: 25.04.2017
 * Time: 13:15
 */

namespace Kaula\TelegramBundle\Telegram\Event;


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
    if (is_null($update->message)) {
      throw new RuntimeException(
        'Message can\'t be null for the ChatNewPhotoEvent.'
      );
    }
    if (empty($update->message->new_chat_photo)) {
      throw new RuntimeException(
        'Chat photo of the Message can\'t be empty for the ChatNewPhotoEvent.'
      );
    }

    $this->setMessage($update->message)
      ->setUpdate($update);
  }

  /**
   * @return PhotoSizeArray
   */
  public function getChatPhotoSizes(): PhotoSizeArray
  {
    return $this->getMessage()->new_chat_photo;
  }

}