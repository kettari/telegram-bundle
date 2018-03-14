<?php

namespace Kettari\TelegramBundle\Telegram\Command\Menu;


interface MenuOptionInterface
{
  /**
   * Returns option caption.
   *
   * @return string
   */
  public function getCaption(): string;

  /**
   * Returns option callback ID.
   */
  public function getCallbackId(): string;

  /**
   * @return HookHandleInterface
   */
  public function getHandler(): HookHandleInterface;

  /**
   * @return bool
   */
  public function checkIsClicked(): bool;

  /**
   * Executed when user clicked this option.
   *
   * @return bool True if option class expects callback from the user.
   */
  public function click(): bool;
}