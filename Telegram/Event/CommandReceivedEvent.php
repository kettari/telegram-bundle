<?php
/**
 * Created by PhpStorm.
 * User: ant
 * Date: 25.04.2017
 * Time: 13:15
 */

namespace Kaula\TelegramBundle\Telegram\Event;


class CommandReceivedEvent extends AbstractCommandEvent
{
  const NAME = 'telegram.command.received';
}