<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram\Event;


use unreal4u\TelegramAPI\Abstracts\TelegramMethods;

abstract class AbstractMethodEvent extends AbstractTelegramEvent
{
  /**
   * @var \unreal4u\TelegramAPI\Abstracts\TelegramMethods
   */
  private $method;

  /**
   * @var mixed
   */
  private $response;

  /**
   * RequestSentEvent constructor.
   *
   * @param TelegramMethods $method
   * @param mixed $response
   */
  public function __construct(TelegramMethods $method, $response)
  {
    parent::__construct();
    $this->method = $method;
    $this->response = $response;
    // @TODO Clarify response type in the comment
  }

  /**
   * @return \unreal4u\TelegramAPI\Abstracts\TelegramMethods
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