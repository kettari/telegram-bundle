<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram\Event;


class CommandReceivedEvent extends AbstractCommandEvent
{
  const NAME = 'telegram.command.received';
}