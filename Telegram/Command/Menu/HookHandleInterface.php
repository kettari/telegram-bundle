<?php

namespace Kettari\TelegramBundle\Telegram\Command\Menu;


use unreal4u\TelegramAPI\Telegram\Types\Update;

interface HookHandleInterface
{
  /**
   * Hook handler. Class implementing this interface can be registered
   * as hook handler.
   *
   * @param \unreal4u\TelegramAPI\Telegram\Types\Update $update
   * @param mixed $parameter
   */
  public function handler(Update $update, $parameter);
}