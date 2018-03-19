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
   * @param mixed $parameter Anything useful to keep state between hooks
   */
  public function handler(Update $update, $parameter);

  /**
   * Create a hook pointing to this class object.
   *
   * @param \unreal4u\TelegramAPI\Telegram\Types\Update $update
   */
  public function hookMySelf(Update $update);
}