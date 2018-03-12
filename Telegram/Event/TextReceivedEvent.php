<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram\Event;


use unreal4u\TelegramAPI\Telegram\Types\Update;

class TextReceivedEvent extends AbstractMessageEvent
{
  const NAME = 'telegram.text.received';

  /**
   * TextReceivedEvent constructor.
   *
   * @param Update $update
   */
  public function __construct(Update $update)
  {
    parent::__construct($update);
    if (empty($update->message->text)) {
      throw new \RuntimeException(
        'Text of the Message can\'t be empty for the TextReceivedEvent.'
      );
    }
  }
}