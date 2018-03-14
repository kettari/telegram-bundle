<?php

namespace Kettari\TelegramBundle\Telegram\Command\Menu;


interface HookHandleInterface
{
  /**
   * Hook handler. Class implementing this interface can be registered
   * as hook handler.
   *
   * @param mixed $parameter Anything useful to keep state between hooks
   */
  public function handler($parameter);

  /**
   * Create a hook pointing to this class object.
   */
  public function hookMySelf();
}