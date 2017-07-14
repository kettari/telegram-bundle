<?php
/**
 * Created by PhpStorm.
 * User: ant
 * Date: 26.04.2017
 * Time: 13:15
 */

namespace Kaula\TelegramBundle\Telegram\Event;


use Psr\Http\Message\ResponseInterface;

abstract class AbstractMethodExceptionEvent extends AbstractMethodEvent
{
  /**
   * Get the associated response
   *
   * @return ResponseInterface|null
   */
  public function getResponse()
  {
    return parent::getResponse();
  }
}