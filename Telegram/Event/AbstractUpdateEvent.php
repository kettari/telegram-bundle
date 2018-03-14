<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram\Event;


use unreal4u\TelegramAPI\Abstracts\TelegramMethods;
use unreal4u\TelegramAPI\Telegram\Types\Update;

abstract class AbstractUpdateEvent extends AbstractTelegramEvent
{
  /**
   * @var Update
   */
  private $update;

  /**
   * @var \unreal4u\TelegramAPI\Abstracts\TelegramMethods
   */
  private $response;

  /**
   * AbstractUpdateEvent constructor.
   *
   * @param Update $update
   */
  public function __construct(Update $update)
  {
    parent::__construct();
    $this->update = $update;
  }

  /**
   * @return Update
   */
  public function getUpdate(): Update
  {
    return $this->update;
  }

  /**
   * @return null|\unreal4u\TelegramAPI\Abstracts\TelegramMethods
   */
  public function getResponse()
  {
    return $this->response;
  }

  /**
   * @param \unreal4u\TelegramAPI\Abstracts\TelegramMethods $response
   * @return AbstractUpdateEvent
   */
  public function setResponse(TelegramMethods $response): AbstractUpdateEvent
  {
    $this->response = $response;

    return $this;
  }

}