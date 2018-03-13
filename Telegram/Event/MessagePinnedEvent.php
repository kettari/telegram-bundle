<?php
declare(strict_types=1);

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
    parent::__construct($update);
    if (empty($update->message->pinned_message)) {
      throw new RuntimeException(
        'Pinned message of the Message can\'t be empty for the MessagePinnedEvent.'
      );
    }
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