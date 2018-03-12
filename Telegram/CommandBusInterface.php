<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram;


use Kettari\TelegramBundle\Telegram\Command\TelegramCommandInterface;
use unreal4u\TelegramAPI\Telegram\Types\Update;
use unreal4u\TelegramAPI\Telegram\Types\User as TelegramUser;

interface CommandBusInterface
{
  /**
   * Registers command.
   *
   * @param string $commandClass
   * @return \Kettari\TelegramBundle\Telegram\CommandBusInterface
   */
  public function registerCommand(string $commandClass): CommandBusInterface;

  /**
   * Return TRUE if command is registered.
   *
   * @param string $commandName
   * @return bool
   */
  public function isCommandRegistered(string $commandName): bool;

  /**
   * Executes command that is registered with CommandBus.
   *
   * @param \unreal4u\TelegramAPI\Telegram\Types\Update $update
   * @param string $commandName
   * @param string $parameter
   * @return bool Returns true if command was executed; false if not found or
   *   user has insufficient permissions.
   */
  public function executeCommand(
    Update $update,
    string $commandName,
    string $parameter = ''
  ): bool;

  /**
   * Return true if telegram user is authorized to execute specified command.
   *
   * @param \unreal4u\TelegramAPI\Telegram\Types\User $tu
   * @param \Kettari\TelegramBundle\Telegram\Command\TelegramCommandInterface $command
   * @return bool
   */
  public function isAuthorized(
    TelegramUser $tu,
    TelegramCommandInterface $command
  ): bool;

  /**
   * Returns array of commands classes.
   *
   * @return array
   */
  public function getCommands(): array;
}