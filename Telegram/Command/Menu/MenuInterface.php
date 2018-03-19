<?php

namespace Kettari\TelegramBundle\Telegram\Command\Menu;

use unreal4u\TelegramAPI\Telegram\Types\Update;

interface MenuInterface
{
  /**
   * Show menu to user.
   *
   * @param \unreal4u\TelegramAPI\Telegram\Types\Update $update
   */
  public function show(Update $update);
}