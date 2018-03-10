<?php
/**
 * Created by PhpStorm.
 * User: ant
 * Date: 16.03.2017
 * Time: 18:11
 */

namespace Kettari\TelegramBundle\Telegram;


use Kettari\TelegramBundle\Entity\Permission;
use Kettari\TelegramBundle\Entity\Role;
use Kettari\TelegramBundle\Exception\InvalidCommand;
use Kettari\TelegramBundle\Telegram\Command\AbstractCommand;
use Kettari\TelegramBundle\Telegram\Event\CommandUnauthorizedEvent;
use Psr\Log\LoggerInterface;
use unreal4u\TelegramAPI\Telegram\Types\Update;
use unreal4u\TelegramAPI\Telegram\Types\User as TelegramUser;


class CommandBus
{

  /**
   * Bot this command bus belongs to.
   *
   * @var Bot
   */
  protected $bot;

  /**
   * @var Hooker
   */
  protected $hooker;

  /**
   * Commands classes.
   *
   * @var array
   */
  protected $commandsClasses = [];

  /**
   * CommandBus constructor.
   *
   * @param \Kettari\TelegramBundle\Telegram\Bot $bot
   * @param \Kettari\TelegramBundle\Telegram\Hooker $hooker
   */
  public function __construct(Bot $bot, Hooker $hooker = null)
  {
    $this->bot = $bot;
    if (!is_null($hooker)) {
      $this->hooker = $hooker;
    } else {
      $this->hooker = new Hooker($this);
    }
  }

  /**
   * Registers command.
   *
   * @param string $commandClass
   * @return \Kettari\TelegramBundle\Telegram\CommandBus
   */
  public function registerCommand($commandClass)
  {
    if (class_exists($commandClass)) {
      if (is_subclass_of($commandClass, AbstractCommand::class)) {
        $this->commandsClasses[$commandClass] = true;
      } else {
        throw new InvalidCommand(
          'Unable to register command: '.$commandClass.
          ' The command should be a subclass of '.AbstractCommand::class
        );
      }
    } else {
      throw new InvalidCommand(
        'Unable to register command: '.$commandClass.' Class is not found'
      );
    }

    return $this;
  }

  /**
   * Return TRUE if command is registered.
   *
   * @param string $commandName
   * @return bool
   */
  public function isCommandRegistered($commandName)
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
   * Executes command that is registered with CommandBus.
   *
   * @param $name
   * @param string $parameter
   * @param \unreal4u\TelegramAPI\Telegram\Types\Update $update
   * @return bool Returns true if command was executed; false if not found or
   *   user has insufficient permissions.
   */
  public function executeCommand($name, $parameter = null, Update $update)
  {
    /** @var LoggerInterface $l */
    $l = $this->getBot()
      ->getContainer()
      ->get('logger');

    foreach ($this->commandsClasses as $commandClass => $placeholder) {
      /** @var AbstractCommand $commandClass */
      if ($commandClass::getName() == $name) {

        // Check permissions for current user
        if (!$this->isAuthorized($update->message->from, $commandClass)) {
          $l->notice(
            'User is not authorized to execute /{command_name} command with the class "{class_name}"',
            ['command_name' => $name, 'class_name' => $commandClass]
          );

          // Dispatch command is unauthorized
          $this->dispatchUnauthorizedCommand($update, $name, $parameter);

          return false;
        }

        // User is authorized, OK
        $l->info(
          'Executing /{command_name} command with the class "{class_name}"',
          ['command_name' => $name, 'class_name' => $commandClass]
        );

        /** @var AbstractCommand $command */
        $command = new $commandClass($this, $update);
        $command->initialize($parameter)
          ->execute();

        return true;
      }
    }

    return false;
  }

  /**
   * Returns bot object.
   *
   * @return \Kettari\TelegramBundle\Telegram\Bot
   */
  public function getBot()
  {
    return $this->bot;
  }

  /**
   * Return TRUE if telegram user is authorized to execute specified command.
   *
   * @param \unreal4u\TelegramAPI\Telegram\Types\User $tu
   * @param $commandClass
   * @return bool
   */
  public function isAuthorized(TelegramUser $tu, $commandClass)
  {
    if (is_null($tu)) {
      return false;
    }

    $l = $this->getBot()
      ->getContainer()
      ->get('logger');

    $d = $this->getBot()
      ->getContainer()
      ->get('doctrine');

    // Find user object
    $user = $d->getRepository('KettariTelegramBundle:User')
      ->findOneBy(['telegram_id' => $tu->id]);
    if (is_null($user)) {
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

    /** @var AbstractCommand $commandClass */
    $requiredPermissions = $commandClass::getRequiredPermissions();

    // First check required permissions against existing permissions
    // and then check all required permissions are present
    $applicablePermissions = array_intersect(
      $requiredPermissions,
      $userPermissions
    );
    $result = !array_diff($applicablePermissions, $requiredPermissions) &&
      !array_diff($requiredPermissions, $applicablePermissions);

    // Write to the log permissions check result
    $l->info(
      'Authorization check: {auth_check}',
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
    $commandName,
    $parameter
  ) {
    $dispatcher = $this->getBot()
      ->getEventDispatcher();

    // Dispatch command event
    $commandUnauthorizedEvent = new CommandUnauthorizedEvent(
      $update, $commandName, $parameter
    );
    $dispatcher->dispatch(
      CommandUnauthorizedEvent::NAME,
      $commandUnauthorizedEvent
    );
  }

  /**
   * Returns array of commands classes.
   *
   * @return array
   */
  public function getCommands()
  {
    return $this->commandsClasses;
  }

  /**
   * @return Hooker
   */
  public function getHooker(): Hooker
  {
    return $this->hooker;
  }

}