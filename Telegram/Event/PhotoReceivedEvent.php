<?php
declare(strict_types=1);

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
    parent::__construct($update);
    if (empty($update->message->photo)) {
      throw new RuntimeException(
        'Photo of the Message can\'t be empty for the PhotoReceivedEvent.'
      );
    }
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
  public function getCaption(): string
  {
    return $this->getMessage()->caption ? $this->getMessage()->caption : '';
  }

}