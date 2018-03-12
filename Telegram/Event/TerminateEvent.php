<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram\Event;

class TerminateEvent extends AbstractUpdateEvent
{
  const NAME = 'telegram.terminate';
}