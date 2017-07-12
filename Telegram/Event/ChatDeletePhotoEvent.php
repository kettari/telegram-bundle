<?php
/**
 * Created by PhpStorm.
 * User: ant
 * Date: 25.04.2017
 * Time: 13:15
 */

namespace Kaula\TelegramBundle\Telegram\Event;


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
    if (is_null($update->message)) {
      throw new RuntimeException(
        'Message can\'t be null for the ChatDeletePhotoEvent.'
      );
    }
    if (empty($update->message->delete_chat_photo)) {
      throw new RuntimeException(
        'Chat delete photo of the Message can\'t be empty for the ChatDeletePhotoEvent.'
      );
    }

    $this->setMessage($update->message)
      ->setUpdate($update);
  }

}