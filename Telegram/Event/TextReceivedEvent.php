<?php
/**
 * Created by PhpStorm.
 * User: ant
 * Date: 25.04.2017
 * Time: 13:15
 */

namespace Kaula\TelegramBundle\Telegram\Event;


use SensioLabs\Security\Exception\RuntimeException;
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
    if (is_null($update->message)) {
      throw new RuntimeException(
        'Message can\'t be null for the TextReceivedEvent.'
      );
    }
    if (empty($update->message->text)) {
      throw new RuntimeException(
        'Text of the Message can\'t be empty for the TextReceivedEvent.'
      );
    }

    $this->setMessage($update->message)
      ->setUpdate($update);
  }

  /**
   * @return string
   */
  public function getText(): string
  {
    return $this->getMessage()->text;
  }

}