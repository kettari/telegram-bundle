<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram\Event;

class LeftChatMemberEvent extends AbstractMessageEvent
{
  const NAME = 'telegram.chatmember.left';
}