<?php
/**
 * Created by PhpStorm.
 * User: ant
 * Date: 25.04.2017
 * Time: 13:15
 */

namespace Kettari\TelegramBundle\Telegram\Event;


use unreal4u\TelegramAPI\Telegram\Types\Update;

class UpdateReceivedEvent extends AbstractUpdateEvent
{
  const NAME = 'telegram.update.received';

  /**
   * UpdateReceivedEvent constructor.
   *
   * @param Update $update
   */
  public function __construct(Update $update)
  {
    $this->setUpdate($update);
  }

}