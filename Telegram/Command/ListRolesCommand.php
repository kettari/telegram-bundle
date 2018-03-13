<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram\Command;

use Kettari\TelegramBundle\Telegram\Communicator;
use Symfony\Bridge\Doctrine\RegistryInterface;

class ListRolesCommand extends AbstractCommand
{

  static public $name = 'listroles';
  static public $description = 'Показать список ролей и разрешений';
  static public $requiredPermissions = ['execute command listroles'];

  /**
   * Executes command.
   */
  public function execute()
  {
    if ('private' == $this->update->message->chat->type) {
      $rolesAndPermissions = $this->getRolesAndPermissions($this->bus->getDoctrine());

      $text = 'Список ролей и разрешений:'.PHP_EOL.PHP_EOL;
      if (count($rolesAndPermissions)) {
        foreach ($rolesAndPermissions as $roleName => $permissions) {
          $text .= '<b>'.$roleName.'</b>'.PHP_EOL;
          foreach ($permissions as $permissionItem) {
            $text .= $permissionItem.PHP_EOL;
          }
          $text .= PHP_EOL;
        }
      } else {
        $text .= 'Роли в системе не определены.';
      }

      $this->replyWithMessage($text, Communicator::PARSE_MODE_HTML);
    } else {
      $this->replyWithMessage(
        'Эта команда работает только в личной переписке с ботом. В общем канале просмотр ролей невозможен.'
      );
    }
  }


  /**
   * Returns array with roles and permissions defined in the database.
   *
   * @param \Symfony\Bridge\Doctrine\RegistryInterface $doctrine
   * @return array
   */
  private function getRolesAndPermissions(RegistryInterface $doctrine)
  {
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