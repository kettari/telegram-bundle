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
   * @param string $command_class
   * @return bool
   */
  public function isCommandRegistered($command_class)
  {
    return isset($this->commands_classes[$command_class]);
  }

  /**
   * Executes command that is registered with CommandBus.
   *
   * @param $command_name
   * @param \unreal4u\TelegramAPI\Telegram\Types\Update $update
   * @return bool Returns TRUE if appropriate command was found.
   */
  public function executeCommand($command_name, Update $update)
  {
    /** @var LoggerInterface $l */
    $l = $this->getBot()
      ->getContainer()
      ->get('logger');

    foreach ($this->commands_classes as $command_class => $placeholder) {
      /** @var AbstractCommand $command_class */
      if ($command_class::getName() == $command_name) {

        // Check permissions for current user
        if (!$this->isAuthorized($update->message->from, $command_class)) {
          $l->notice(
            'User is not authorized to execute /{command_name} command with the class "{class_name}"',
            ['command_name' => $command_name, 'class_name' => $command_class]
          );

          $this->getBot()
            ->sendMessage(
              $update->message->chat->id,
              'Извините, у вас недостаточно прав для доступа к этой команде.'
            );

          return false;
        }

        // User is authorized
        $l->info(
          'Executing /{command_name} command with the class "{class_name}"',
          ['command_name' => $command_name, 'class_name' => $command_class]
        );

        /** @var AbstractCommand $command */
        $command = new $command_class($this, $update);
        $command->initialize()
          ->execute();

        return true;
      }
    }
    $l->notice(
      'No class registered to handle /{command_name} command',
      ['command_name' => $command_name]
    );

    return false;
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

    // Special utility to check equality of arrays
    /*function array_equal_values(array $a, array $b) {
      return !array_diff($a, $b) && !array_diff($b, $a);
    }*/

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
    /*dump($required_permissions);
    dump($existing_permissions);
    die;*/
    $applicable_permissions = array_intersect(
      $required_permissions,
      $user_permissions
    );

    // Write to the log permissions check result
    $l->info(
      'Authorization check',
      [
        'required_permissions'   => $required_permissions,
        'user_permissions'       => $user_permissions,
        'applicable_permissions' => $applicable_permissions,
      ]
    );

    return !array_diff($applicable_permissions, $required_permissions) &&
      !array_diff($required_permissions, $applicable_permissions);
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
   * Returns bot object.
   *
   * @return \Kaula\TelegramBundle\Telegram\Bot
   */
  public function getBot()
  {
    return $this->bot;
  }

  /**
   * @return Hooker
   */
  public function getHooker(): Hooker
  {
    return $this->hooker;
  }

}