<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram\Event;


class GroupCreatedEvent extends AbstractMessageEvent
{
  const NAME = 'telegram.group.created';
}