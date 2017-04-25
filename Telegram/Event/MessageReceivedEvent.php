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

class MessageReceivedEvent extends AbstractMessageEvent
{
  const NAME = 'telegram.message.received';

  /**
   * MessageReceivedEvent constructor.
   *
   * @param Update $update
   */
  public function __construct(Update $update)
  {
    if (is_null($update->message)) {
      throw new RuntimeException('Message can\'t be null for the MessageReceivedEvent.');
    }

    $this->setMessage($update->message)
      ->setUpdate($update);
  }

}