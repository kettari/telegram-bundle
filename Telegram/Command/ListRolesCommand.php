<?php
/**
 * Created by PhpStorm.
 * User: ant
 * Date: 16.03.2017
 * Time: 18:18
 */

namespace Kaula\TelegramBundle\Telegram\Command;


use Doctrine\Bundle\DoctrineBundle\Registry;

class ListRolesCommand extends AbstractCommand
{

  static public $name = 'listroles';
  static public $description = 'Показать список ролей и разрешений';
  static public $required_permissions = ['execute command listroles'];

  /**
   * Executes command.
   */
  public function execute()
  {
    $d = $this->getBus()
      ->getBot()
      ->getContainer()
      ->get('doctrine');
    $roles_and_permissions = $this->getRolesAndPermissions($d);

    $text = 'Список ролей и разрешений:'.PHP_EOL.PHP_EOL;
    if (count($roles_and_permissions)) {
      foreach ($roles_and_permissions as $role_name => $permissions) {
        $text .= '<b>'.$role_name.'</b>'.PHP_EOL;
        foreach ($permissions as $permission_item) {
          $text .= $permission_item.PHP_EOL;
        }
        $text .= PHP_EOL;
      }
    } else {
      $text .= 'Роли в системе не определены.';
    }

    $this->replyWithMessage($text, self::PARSE_MODE_HTML);
  }


  /**
   * Returns array with roles and permissions defined in the database.
   *
   * @param \Doctrine\Bundle\DoctrineBundle\Registry $d
   * @return array
   */
  private function getRolesAndPermissions(Registry $d)
  {
    $result = [];
    $roles = $d->getRepository('KaulaTelegramBundle:Role')
      ->findAll();
    /** @var \Kaula\TelegramBundle\Entity\Role $role_item */
    foreach ($roles as $role_item) {
      $permissions = $role_item->getPermissions();
      $perms = [];
      /** @var \Kaula\TelegramBundle\Entity\Permission $permission_item */
      foreach ($permissions as $permission_item) {
        $perms[] = $permission_item->getName();
      }
      asort($perms);
      $result[$role_item->getName()] = $perms;
    }
    ksort($result);

    return $result;
  }

}