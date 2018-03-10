<?php
/**
 * Created by PhpStorm.
 * User: ant
 * Date: 26.04.2017
 * Time: 13:15
 */

namespace Kettari\TelegramBundle\Telegram\Event;


class RequestThrottleEvent extends AbstractMethodExceptionEvent
{
  const NAME = 'telegram.request.throttled';
}