<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram\Command;

use Kettari\TelegramBundle\Telegram\Communicator;

class ListRolesCommand extends AbstractCommand
{

  static public $name = 'listroles';
  static public $description = 'command.listroles.description';
  static public $requiredPermissions = ['execute command listroles'];

  /**
   * Executes command.
   */
  public function execute()
  {
    // This command is available only in private chat
    if ('private' != $this->update->message->chat->type) {
      $this->replyWithMessage(
        $this->trans->trans('command.private_only')
      );

      return;
    }

    // Collect all roles and permissions from database
    $rolesAndPermissions = $this->getRolesAndPermissions();
    $text = $this->trans->trans('command.listroles.list_of_roles').PHP_EOL.
      PHP_EOL;
    if (count($rolesAndPermissions)) {
      foreach ($rolesAndPermissions as $roleName => $permissions) {
        $text .= '<b>'.$roleName.'</b>'.PHP_EOL;
        foreach ($permissions as $permissionItem) {
          $text .= $permissionItem.PHP_EOL;
        }
        $text .= PHP_EOL;
      }
    } else {
      $text .= $this->trans->trans('command.listroles.roles_undefined');
    }

    $this->replyWithMessage($text, Communicator::PARSE_MODE_HTML);
  }


  /**
   * Returns array with roles and permissions defined in the database.
   *
   * @return array
   */
  private function getRolesAndPermissions()
  {
    $doctrine = $this->bus->getDoctrine();
    $result = [];
    $roles = $doctrine->getRepository('KettariTelegramBundle:Role')
      ->findAll();
    /** @var \Kettari\TelegramBundle\Entity\Role $roleItem */
    foreach ($roles as $roleItem) {
      $permissions = $roleItem->getPermissions();
      $perms = [];
      /** @var \Kettari\TelegramBundle\Entity\Permission $permissionItem */
      foreach ($permissions as $permissionItem) {
        $perms[] = $permissionItem->getName();
      }
      asort($perms);
      $result[$roleItem->getName()] = $perms;
    }
    ksort($result);

    return $result;
  }

}