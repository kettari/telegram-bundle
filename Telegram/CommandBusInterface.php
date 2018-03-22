<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram;


use Kettari\TelegramBundle\Entity\Chat;
use Kettari\TelegramBundle\Entity\Hook;
use Kettari\TelegramBundle\Entity\User;
use Kettari\TelegramBundle\Telegram\Command\TelegramCommandInterface;
use unreal4u\TelegramAPI\Telegram\Types\Update;
use unreal4u\TelegramAPI\Telegram\Types\User as TelegramUser;

interface CommandBusInterface
{
  /**
   * Registers command.
   *
   * @param string $commandName
   * @param string $serviceId
   * @return \Kettari\TelegramBundle\Telegram\CommandBusInterface
   */
  public function registerCommand(string $commandName, string $serviceId): CommandBusInterface;

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
   * @param string $commandParameter
   * @return bool Returns true if command was executed; false if not found or
   *   user has insufficient permissions.
   */
  public function executeCommand(
    Update $update,
    string $commandName,
    string $commandParameter = ''
  ): bool;

  /**
   * Return true if telegram user is authorized to execute specified command.
   *
   * @param \unreal4u\TelegramAPI\Telegram\Types\User $telegramUser
   * @param \Kettari\TelegramBundle\Telegram\Command\TelegramCommandInterface $command
   * @return bool
   */
  public function isAuthorized(TelegramUser $telegramUser, TelegramCommandInterface $command): bool;

  /**
   * Returns array of all commands objects.
   *
   * @return array
   */
  public function getCommands(): array;

  /**
   * Creates hook.
   *
   * @param \unreal4u\TelegramAPI\Telegram\Types\Update $update
   * @param string $serviceId
   * @param string $methodName
   * @param string $parameter
   */
  public function createHook(
    Update $update,
    string $serviceId,
    string $methodName = 'handler',
    string $parameter = ''
  );

  /**
   * Finds hook.
   *
   * @param \unreal4u\TelegramAPI\Telegram\Types\Update $update
   * @return Hook|null
   */
  public function findHook(Update $update);

  /**
   * Executes the hook.
   *
   * @param \Kettari\TelegramBundle\Entity\Hook $hook
   * @param \unreal4u\TelegramAPI\Telegram\Types\Update $update
   * @return \Kettari\TelegramBundle\Telegram\CommandBusInterface
   */
  public function executeHook(Hook $hook, Update $update): CommandBusInterface;

  /**
   * Deletes all hooks for the update.
   *
   * @param \unreal4u\TelegramAPI\Telegram\Types\Update $update
   */
  public function deleteAllHooks(Update $update);

  /**
   * Deletes the hook.
   *
   * @param \Kettari\TelegramBundle\Entity\Hook $hook
   */
  public function deleteHook(Hook $hook);

  /**
   * Sets flag that request is handled.
   *
   * @param bool $handled
   */
  public function setRequestHandled(bool $handled);
}