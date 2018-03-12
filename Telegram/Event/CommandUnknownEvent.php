<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram\Event;


class CommandUnknownEvent extends AbstractCommandEvent
{
  const NAME = 'telegram.command.unknown';
}