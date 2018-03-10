<?php
/**
 * Created by PhpStorm.
 * User: ant
 * Date: 26.04.2017
 * Time: 13:15
 */

namespace Kettari\TelegramBundle\Telegram\Event;


class RequestExceptionEvent extends AbstractMethodExceptionEvent
{
  const NAME = 'telegram.request.exception';

  /**
   * @var string
   */
  private $exception_message;

  /**
   * RequestSentEvent constructor.
   *
   * @param mixed $method
   * @param mixed $response
   * @param string $exception_message
   */
  public function __construct($method, $response, $exception_message)
  {
    parent::__construct($method, $response);
    $this->exception_message = $exception_message;
  }

  /**
   * @return string
   */
  public function getExceptionMessage(): string
  {
    return $this->exception_message;
  }

}