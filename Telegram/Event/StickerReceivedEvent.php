<?php
/**
 * Created by PhpStorm.
 * User: ant
 * Date: 25.04.2017
 * Time: 13:15
 */

namespace Kettari\TelegramBundle\Telegram\Event;


use RuntimeException;
use unreal4u\TelegramAPI\Telegram\Types\Sticker;
use unreal4u\TelegramAPI\Telegram\Types\Update;

class StickerReceivedEvent extends AbstractMessageEvent
{
  const NAME = 'telegram.sticker.received';

  /**
   * StickerReceivedEvent constructor.
   *
   * @param Update $update
   */
  public function __construct(Update $update)
  {
    if (is_null($update->message)) {
      throw new RuntimeException(
        'Message can\'t be null for the StickerReceivedEvent.'
      );
    }
    if (empty($update->message->sticker)) {
      throw new RuntimeException(
        'Sticker of the Message can\'t be empty for the StickerReceivedEvent.'
      );
    }

    $this->setMessage($update->message)
      ->setUpdate($update);
  }

  /**
   * @return Sticker
   */
  public function getSticker(): Sticker
  {
    return $this->getMessage()->sticker;
  }

}