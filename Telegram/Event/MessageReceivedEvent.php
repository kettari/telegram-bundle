<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram\Event;

class MessageReceivedEvent extends AbstractMessageEvent
{
  const NAME = 'telegram.message.received';
}