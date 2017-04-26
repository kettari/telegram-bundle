<?php
/**
 * Created by PhpStorm.
 * User: ant
 * Date: 26.04.2017
 * Time: 13:15
 */

namespace Kaula\TelegramBundle\Telegram\Event;


abstract class AbstractMethodEvent extends AbstractTelegramEvent
{
  /**
   * @var mixed
   */
  private $method;

  /**
   * @var mixed
   */
  private $response;

  /**
   * RequestSentEvent constructor.
   *
   * @param mixed $method
   * @param mixed $response
   */
  public function __construct($method, $response)
  {
    $this->method = $method;
    $this->response = $response;
  }

  /**
   * @return mixed
   */
  public function getMethod()
  {
    return $this->method;
  }

  /**
   * @return mixed
   */
  public function getResponse()
  {
    return $this->response;
  }
}