<?php
/**
 * Created by PhpStorm.
 * User: ant
 * Date: 25.04.2017
 * Time: 13:15
 */

namespace Kaula\TelegramBundle\Telegram\Event;


use unreal4u\TelegramAPI\Telegram\Types\Message;

abstract class AbstractMessageEvent extends AbstractUpdateEvent
{

  /**
   * @var Message
   */
  private $message;

  /**
   * @return Message
   */
  public function getMessage(): Message
  {
    return $this->message;
  }

  /**
   * @param Message $message
   * @return AbstractMessageEvent
   */
  public function setMessage(Message $message): AbstractMessageEvent
  {
    $this->message = $message;

    return $this;
  }

}