<?php
/**
 * Created by PhpStorm.
 * User: ant
 * Date: 25.04.2017
 * Time: 19:03
 */

namespace Kaula\TelegramBundle\Telegram\Event;


class JoinChatMemberEvent extends AbstractMessageEvent
{
  const NAME = 'telegram.chatmember.joined';
}