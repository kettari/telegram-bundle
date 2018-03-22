<?php

namespace Kettari\TelegramBundle\Telegram\Command\Menu;


use unreal4u\TelegramAPI\Telegram\Types\Update;

interface MenuOptionInterface extends HookHandleInterface
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
   * @param \unreal4u\TelegramAPI\Telegram\Types\Update $update
   * @return bool
   */
  public function checkIsClicked(Update $update): bool;

  /**
   * Executed when user clicked this option.
   *
   * @param \unreal4u\TelegramAPI\Telegram\Types\Update $update
   * @return bool True if option class expects callback from the user.
   */
  public function click(Update $update): bool;
}