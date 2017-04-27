<?php
/**
 * Created by PhpStorm.
 * User: ant
 * Date: 16.03.2017
 * Time: 18:11
 */

namespace Kaula\TelegramBundle\Telegram;


use Kaula\TelegramBundle\Entity\Permission;
use Kaula\TelegramBundle\Entity\Role;
use Kaula\TelegramBundle\Exception\InvalidCommand;
use Kaula\TelegramBundle\Telegram\Command\AbstractCommand;
use Kaula\TelegramBundle\Telegram\Event\CommandUnauthorizedEvent;
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
  protected $commands_classes = [];

  /**
   * CommandBus constructor.
   *
   * @param \Kaula\TelegramBundle\Telegram\Bot $bot
   * @param \Kaula\TelegramBundle\Telegram\Hooker $hooker
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
   * @param string $command_class
   * @return \Kaula\TelegramBundle\Telegram\CommandBus
   */
  public function registerCommand($command_class)
  {
    if (class_exists($command_class)) {
      if (is_subclass_of($command_class, AbstractCommand::class)) {
        $this->commands_classes[$command_class] = true;
      } else {
        throw new InvalidCommand(
          'Unable to register command: '.$command_class.
          ' The command should be a subclass of '.AbstractCommand::class
        );
      }
    } else {
      throw new InvalidCommand(
        'Unable to register command: '.$command_class.' Class is not found'
      );
    }

    return $this;
  }

  /**
   * Return TRUE if command is registered.
   *
   * @param string $command_name
   * @return bool
   */
  public function isCommandRegistered($command_name)
  {
    foreach ($this->commands_classes as $command_class => $placeholder) {
      /** @var AbstractCommand $command_class */
      if ($command_class::getName() == $command_name) {
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

    foreach ($this->commands_classes as $command_class => $placeholder) {
      /** @var AbstractCommand $command_class */
      if ($command_class::getName() == $name) {

        // Check permissions for current user
        if (!$this->isAuthorized($update->message->from, $command_class)) {
          $l->notice(
            'User is not authorized to execute /{command_name} command with the class "{class_name}"',
            ['command_name' => $name, 'class_name' => $command_class]
          );

          // Dispatch command is unauthorized
          $this->dispatchUnauthorizedCommand($update, $name, $parameter);

          return false;
        }

        // User is authorized, OK
        $l->info(
          'Executing /{command_name} command with the class "{class_name}"',
          ['command_name' => $name, 'class_name' => $command_class]
        );

        /** @var AbstractCommand $command */
        $command = new $command_class($this, $update);
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
   * @return \Kaula\TelegramBundle\Telegram\Bot
   */
  public function getBot()
  {
    return $this->bot;
  }

  /**
   * Return TRUE if telegram user is authorized to execute specified command.
   *
   * @param \unreal4u\TelegramAPI\Telegram\Types\User $tu
   * @param $command_class
   * @return bool
   */
  public function isAuthorized(TelegramUser $tu, $command_class)
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
    $user = $d->getRepository('KaulaTelegramBundle:User')
      ->findOneBy(['telegram_id' => $tu->id]);
    if (is_null($user)) {
      return false;
    }
    // Fetch roles and iterate permissions
    $roles = $user->getRoles();
    $user_permissions = [];
    /** @var Role $role_item */
    foreach ($roles as $role_item) {
      $permissions_collection = $role_item->getPermissions();
      $permissions_array = [];
      /** @var Permission $permission_item */
      foreach ($permissions_collection as $permission_item) {
        $permissions_array[] = $permission_item->getName();
      }
      $user_permissions = array_merge($user_permissions, $permissions_array);
    }

    /** @var AbstractCommand $command_class */
    $required_permissions = $command_class::getRequiredPermissions();

    // First check required permissions against existing permissions
    // and then check all required permissions are present
    $applicable_permissions = array_intersect(
      $required_permissions,
      $user_permissions
    );
    $result = !array_diff($applicable_permissions, $required_permissions) &&
      !array_diff($required_permissions, $applicable_permissions);

    // Write to the log permissions check result
    $l->info(
      'Authorization check: {auth_check}',
      [
        'auth_check'             => $result ? 'OK' : 'not authorized',
        'required_permissions'   => $required_permissions,
        'user_permissions'       => $user_permissions,
        'applicable_permissions' => $applicable_permissions,
      ]
    );

    return $result;
  }

  /**
   * Dispatches command is unauthorized.
   *
   * @param Update $update
   * @param string $command_name
   * @param string $parameter
   */
  private function dispatchUnauthorizedCommand(
    Update $update,
    $command_name,
    $parameter
  ) {
    $dispatcher = $this->getBot()
      ->getEventDispatcher();

    // Dispatch command event
    $command_unauthorized_event = new CommandUnauthorizedEvent(
      $update, $command_name, $parameter
    );
    $dispatcher->dispatch(
      CommandUnauthorizedEvent::NAME,
      $command_unauthorized_event
    );
  }

  /**
   * Returns array of commands classes.
   *
   * @return array
   */
  public function getCommands()
  {
    return $this->commands_classes;
  }

  /**
   * @return Hooker
   */
  public function getHooker(): Hooker
  {
    return $this->hooker;
  }

}