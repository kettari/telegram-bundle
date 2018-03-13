<?php
declare(strict_types=1);

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
    parent::__construct($update);
    if (empty($update->message->sticker)) {
      throw new RuntimeException(
        'Sticker of the Message can\'t be empty for the StickerReceivedEvent.'
      );
    }
  }

  /**
   * @return Sticker
   */
  public function getSticker(): Sticker
  {
    return $this->getMessage()->sticker;
  }

}