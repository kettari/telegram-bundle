<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram;


use Kettari\TelegramBundle\Entity\Hook;
use Kettari\TelegramBundle\Telegram\Command\TelegramCommandInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
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

  /**
   * Returns Doctrine service.
   *
   * @return \Symfony\Bridge\Doctrine\RegistryInterface
   */
  public function getDoctrine(): RegistryInterface;

  /**
   * Returns event dispatcher service.
   *
   * @return \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  public function getDispatcher(): EventDispatcherInterface;

  /**
   * Returns user's headquarters.
   *
   * @return \Kettari\TelegramBundle\Telegram\UserHqInterface
   */
  public function getUserHq(): UserHqInterface;

  /**
   * Returns Communicator service.
   *
   * @return \Kettari\TelegramBundle\Telegram\CommunicatorInterface
   */
  public function getCommunicator(): CommunicatorInterface;

  /**
   * Returns Pusher service.
   *
   * @return \Kettari\TelegramBundle\Telegram\PusherInterface
   */
  public function getPusher(): PusherInterface;

  /**
   * Creates hook.
   *
   * @param \unreal4u\TelegramAPI\Telegram\Types\Update $update
   * @param string $className
   * @param string $methodName
   * @param string $parameters
   */
  public function createHook(
    Update $update,
    string $className,
    string $methodName,
    string $parameters = ''
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
   * Deletes the hook.
   *
   * @param \Kettari\TelegramBundle\Entity\Hook $hook
   */
  public function deleteHook(Hook $hook);
}