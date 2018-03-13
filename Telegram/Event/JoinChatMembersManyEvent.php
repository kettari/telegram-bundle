<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram\Event;


class JoinChatMembersManyEvent extends AbstractMessageEvent
{
  const NAME = 'telegram.chatmembers.joined';
}