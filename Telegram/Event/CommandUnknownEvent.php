<?php
/**
 * Created by PhpStorm.
 * User: ant
 * Date: 25.04.2017
 * Time: 13:15
 */

namespace Kettari\TelegramBundle\Telegram\Event;


class CommandUnknownEvent extends AbstractCommandEvent
{
  const NAME = 'telegram.command.unknown';
}