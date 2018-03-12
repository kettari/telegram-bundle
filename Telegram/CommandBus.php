<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram;

use Kettari\TelegramBundle\Entity\Permission;
use Kettari\TelegramBundle\Entity\Role;
use Kettari\TelegramBundle\Exception\InvalidCommandException;
use Kettari\TelegramBundle\Telegram\Command\AbstractCommand;
use Kettari\TelegramBundle\Telegram\Command\TelegramCommandInterface;
use Kettari\TelegramBundle\Telegram\Event\CommandExecutedEvent;
use Kettari\TelegramBundle\Telegram\Event\CommandUnauthorizedEvent;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use unreal4u\TelegramAPI\Telegram\Types\Update;
use unreal4u\TelegramAPI\Telegram\Types\User as TelegramUser;


class CommandBus implements CommandBusInterface
{
  /**
   * Commands classes.
   *
   * @var array
   */
  protected $commandsClasses = [];

  /**
   * @var LoggerInterface
   */
  private $logger;

  /**
   * @var RegistryInterface
   */
  private $doctrine;

  /**
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  private $dispatcher;

  /**
   * CommandBus constructor.
   *
   * @param \Psr\Log\LoggerInterface $logger
   * @param \Symfony\Bridge\Doctrine\RegistryInterface $doctrine
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher
   */
  public function __construct(
    LoggerInterface $logger,
    RegistryInterface $doctrine,
    EventDispatcherInterface $dispatcher
  ) {
    $this->logger = $logger;
    $this->doctrine = $doctrine;
    $this->dispatcher = $dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public function registerCommand(string $commandClass): CommandBusInterface
  {
    $this->logger->debug(
      'About to register command class "{command_class}"',
      ['command_class' => $commandClass]
    );

    if (class_exists($commandClass)) {
      if ($commandClass instanceof TelegramCommandInterface) {
        $this->commandsClasses[$commandClass] = true;
      } else {
        throw new InvalidCommandException(
          'Unable to register command: "'.$commandClass.
          '" The command should implement '.TelegramCommandInterface::class.
          ' interface'
        );
      }
    } else {
      throw new InvalidCommandException(
        'Unable to register command: '.$commandClass.' Class is not found'
      );
    }

    $this->logger->debug(
      'Command class "{command_class}" registered',
      ['command_class' => $commandClass]
    );

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isCommandRegistered(string $commandName): bool
  {
    foreach ($this->commandsClasses as $commandClass => $placeholder) {
      /** @var AbstractCommand $commandClass */
      if ($commandClass::getName() == $commandName) {
        return true;
      }
    }

    return false;
  }

  /**
   * {@inheritdoc}
   */
  public function executeCommand(
    Update $update,
    string $commandName,
    string $parameter = ''
  ): bool {
    $this->logger->debug(
      'About to execute command "{command_name}" with parameter "{parameter}" for the update ID={update_id}',
      [
        'command_name' => $commandName,
        'parameter'    => $parameter,
        'update_id'    => $update->update_id,
      ]
    );

    foreach ($this->commandsClasses as $commandClass => $placeholder) {
      /** @var AbstractCommand $commandClass */
      if ($commandClass::getName() == $commandName) {

        // Check permissions for current user
        if (!$this->isAuthorized($update->message->from, $commandClass)) {
          $this->logger->notice(
            'User is not authorized to execute /{command_name} command with the class "{class_name}"',
            ['command_name' => $commandName, 'class_name' => $commandClass]
          );

          // Dispatch command is unauthorized
          $this->dispatchUnauthorizedCommand($update, $commandName, $parameter);

          return false;
        }

        // User is authorized, OK
        $this->logger->info(
          'Executing /{command_name} command with the parameter "{parameter}"',
          [
            'command_name' => $commandName,
            'class_name'   => $commandClass,
            'parameter'    => $parameter,
            'update_id'    => $update->update_id,
          ]
        );

        /** @var AbstractCommand $command */
        $command = new $commandClass($this, $update);
        $command->initialize($parameter)
          ->execute();

        $this->logger->debug(
          'Command /{command_name} executed with the class "{class_name}"',
          ['command_name' => $commandName, 'class_name' => $commandClass]
        );

        // Dispatch command is executed
        $this->dispatchCommandExecuted($update, $command);

        return true;
      }
    }

    return false;
  }

  /**
   * {@inheritdoc}
   */
  public function isAuthorized(
    TelegramUser $tu,
    TelegramCommandInterface $command
  ): bool {
    $this->logger->debug(
      'About to check Telegram user ID={user_id} authorization to execute command "{command_name}"',
      ['user_id' => $tu->id, 'command_name' => $command::getName()]
    );

    // Find user object
    $user = $this->doctrine->getRepository('KettariTelegramBundle:User')
      ->findOneByTelegramId($tu->id);
    if (is_null($user)) {
      $this->logger->debug(
        'Telegram user ID={user_id} not found in the database',
        ['user_id' => $tu->id]
      );

      return false;
    }
    // Fetch roles and iterate permissions
    $roles = $user->getRoles();
    $userPermissions = [];
    /** @var Role $roleItem */
    foreach ($roles as $roleItem) {
      $permissionsCollection = $roleItem->getPermissions();
      $permissionsArray = [];
      /** @var Permission $permissionItem */
      foreach ($permissionsCollection as $permissionItem) {
        $permissionsArray[] = $permissionItem->getName();
      }
      $userPermissions = array_merge($userPermissions, $permissionsArray);
    }

    $requiredPermissions = $command::getRequiredPermissions();

    // First check required permissions against existing permissions
    // and then check all required permissions are present
    $applicablePermissions = array_intersect(
      $requiredPermissions,
      $userPermissions
    );
    $result = !array_diff($applicablePermissions, $requiredPermissions) &&
      !array_diff($requiredPermissions, $applicablePermissions);

    // Write to the log permissions check result
    $this->logger->info(
      'Command authorization check: {auth_check}',
      [
        'auth_check'             => $result ? 'OK' : 'not authorized',
        'required_permissions'   => $requiredPermissions,
        'user_permissions'       => $userPermissions,
        'applicable_permissions' => $applicablePermissions,
      ]
    );

    return $result;
  }

  /**
   * Dispatches command is unauthorized.
   *
   * @param Update $update
   * @param string $commandName
   * @param string $parameter
   */
  private function dispatchUnauthorizedCommand(
    Update $update,
    string $commandName,
    string $parameter
  ) {
    // Dispatch command event
    $commandUnauthorizedEvent = new CommandUnauthorizedEvent(
      $update, $commandName, $parameter
    );
    $this->dispatcher->dispatch(
      CommandUnauthorizedEvent::NAME,
      $commandUnauthorizedEvent
    );
  }

  /**
   * Dispatches command is executed.
   *
   * @param Update $update
   * @param \Kettari\TelegramBundle\Telegram\Command\AbstractCommand $command
   */
  private function dispatchCommandExecuted(
    Update $update,
    AbstractCommand $command
  ) {
    // Dispatch command event
    $commandExecutedEvent = new CommandExecutedEvent($update, $command);
    $this->dispatcher->dispatch(
      CommandExecutedEvent::NAME,
      $commandExecutedEvent
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getCommands(): array
  {
    return $this->commandsClasses;
  }

}