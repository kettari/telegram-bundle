<?php
/**
 * Created by PhpStorm.
 * User: ant
 * Date: 25.04.2017
 * Time: 13:15
 */

namespace Kettari\TelegramBundle\Telegram\Event;


use RuntimeException;
use unreal4u\TelegramAPI\Telegram\Types\Custom\PhotoSizeArray;

use unreal4u\TelegramAPI\Telegram\Types\Update;

class PhotoReceivedEvent extends AbstractMessageEvent
{
  const NAME = 'telegram.photo.received';

  /**
   * PhotoReceivedEvent constructor.
   *
   * @param Update $update
   */
  public function __construct(Update $update)
  {
    if (is_null($update->message)) {
      throw new RuntimeException(
        'Message can\'t be null for the PhotoReceivedEvent.'
      );
    }
    if (empty($update->message->photo)) {
      throw new RuntimeException(
        'Photo of the Message can\'t be empty for the PhotoReceivedEvent.'
      );
    }

    $this->setMessage($update->message)
      ->setUpdate($update);
  }

  /**
   * Message is a photo, available sizes of the photo
   *
   * @return PhotoSizeArray
   */
  public function getPhotoSizes(): PhotoSizeArray
  {
    return $this->getMessage()->photo;
  }

  /**
   * Optional. Caption for the document, photo or video, 0-200 characters
   *
   * @return string
   */
  public function getCaption()
  {
    return $this->getMessage()->caption;
  }

}