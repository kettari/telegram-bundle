<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram\Command;


use unreal4u\TelegramAPI\Telegram\Types\Update;

interface TelegramCommandInterface
{
  /**
   * Returns name of the command.
   *
   * @return string
   */
  static public function getName();

  /**
   * Returns description of the command.
   *
   * @return string
   */
  static public function getDescription();

  /**
   * Returns supported patterns of the command.
   *
   * @return array
   */
  static public function getSupportedPatterns(): array;

  /**
   * Returns visibility flag of the command.
   *
   * @return bool
   */
  static public function isVisible(): bool;

  /**
   * Returns required permissions to execute the command.
   *
   * @return array
   */
  static public function getRequiredPermissions(): array;

  /**
   * Returns notifications declared in the command.
   *
   * @return array
   */
  static public function getDeclaredNotifications(): array;

  /**
   * Executes command.
   *
   * @param \unreal4u\TelegramAPI\Telegram\Types\Update $update
   * @param string $parameter
   * @return void
   */
  public function execute(Update $update, string $parameter = '');
}