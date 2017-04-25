<?php
/**
 * Created by PhpStorm.
 * User: ant
 * Date: 25.04.2017
 * Time: 13:15
 */

namespace Kaula\TelegramBundle\Telegram\Event;


use Symfony\Component\EventDispatcher\Event;
use unreal4u\TelegramAPI\Telegram\Types\Update;

class UpdateIncomingEvent extends AbstractUpdateEvent
{
  const NAME = 'telegram.update.incoming';
}