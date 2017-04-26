<?php
/**
 * Created by PhpStorm.
 * User: ant
 * Date: 26.04.2017
 * Time: 13:15
 */

namespace Kaula\TelegramBundle\Telegram\Event;


class RequestExceptionEvent extends AbstractMethodExceptionEvent
{
  const NAME = 'telegram.request.exception';
}