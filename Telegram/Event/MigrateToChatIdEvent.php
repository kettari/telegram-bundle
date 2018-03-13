<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram\Event;


class MigrateToChatIdEvent extends AbstractMessageEvent
{
  const NAME = 'telegram.chat.migrated_to';
}