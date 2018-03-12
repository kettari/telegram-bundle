<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram\Event;


class CommandUnauthorizedEvent extends AbstractCommandEvent
{
  const NAME = 'telegram.command.unauthorized';
}