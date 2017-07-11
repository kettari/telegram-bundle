<?php
/**
 * Created by PhpStorm.
 * User: ant
 * Date: 25.04.2017
 * Time: 13:15
 */

namespace Kaula\TelegramBundle\Telegram\Event;


class CommandUnauthorizedEvent extends AbstractCommandEvent
{
  const NAME = 'telegram.command.unauthorized';
}