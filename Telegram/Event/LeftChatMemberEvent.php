<?php
/**
 * Created by PhpStorm.
 * User: ant
 * Date: 25.04.2017
 * Time: 19:03
 */

namespace Kaula\TelegramBundle\Telegram\Event;


class LeftChatMemberEvent extends AbstractMessageEvent
{
  const NAME = 'telegram.chatmember.left';
}