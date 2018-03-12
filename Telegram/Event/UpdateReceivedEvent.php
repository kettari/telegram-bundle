<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram\Event;

class UpdateReceivedEvent extends AbstractUpdateEvent
{
  const NAME = 'telegram.update.received';
}