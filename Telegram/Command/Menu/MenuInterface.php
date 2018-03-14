<?php

namespace Kettari\TelegramBundle\Telegram\Command\Menu;

interface MenuInterface
{
  /**
   * Show menu to user.
   */
  public function show();
}