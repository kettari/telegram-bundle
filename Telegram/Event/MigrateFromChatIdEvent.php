<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram\Event;


class MigrateFromChatIdEvent extends AbstractMessageEvent
{
  const NAME = 'telegram.chat.migrated_from';
}