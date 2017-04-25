<?php
/**
 * Created by PhpStorm.
 * User: ant
 * Date: 25.04.2017
 * Time: 13:15
 */

namespace Kaula\TelegramBundle\Telegram\Event;


use unreal4u\TelegramAPI\Telegram\Types\Update;

abstract class AbstractUpdateEvent extends AbstractTelegramEvent
{

  /**
   * @var Update
   */
  private $update;

  /**
   * @return Update
   */
  public function getUpdate(): Update
  {
    return $this->update;
  }

  /**
   * @param Update $update
   * @return AbstractUpdateEvent
   */
  public function setUpdate(Update $update): AbstractUpdateEvent
  {
    $this->update = $update;

    return $this;
  }


}