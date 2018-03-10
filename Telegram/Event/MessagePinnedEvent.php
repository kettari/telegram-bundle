<?php
/**
 * Created by PhpStorm.
 * User: ant
 * Date: 25.04.2017
 * Time: 13:15
 */

namespace Kettari\TelegramBundle\Telegram\Event;


use RuntimeException;
use unreal4u\TelegramAPI\Telegram\Types\Message;
use unreal4u\TelegramAPI\Telegram\Types\Update;

class MessagePinnedEvent extends AbstractMessageEvent
{
  const NAME = 'telegram.message.pinned';

  /**
   * MessagePinnedEvent constructor.
   *
   * @param Update $update
   */
  public function __construct(Update $update)
  {
    if (is_null($update->message)) {
      throw new RuntimeException(
        'Message can\'t be null for the MessagePinnedEvent.'
      );
    }
    if (empty($update->message->pinned_message)) {
      throw new RuntimeException(
        'Pinned message of the Message can\'t be empty for the MessagePinnedEvent.'
      );
    }

    $this->setMessage($update->message)
      ->setUpdate($update);
  }

  /**
   * Specified message was pinned
   *
   * @return Message
   */
  public function getPinnedMessage(): Message
  {
    return $this->getMessage()->pinned_message;
  }

}