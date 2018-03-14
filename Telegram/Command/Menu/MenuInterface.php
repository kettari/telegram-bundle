<?php

namespace Kettari\TelegramBundle\Telegram\Command\Menu;

interface MenuInterface
{
  /**
   * Show menu to user.
   *
   * @param int $chatId Telegram chat ID.
   */
  public function show(int $chatId);
}