<?php
/**
 * Created by PhpStorm.
 * User: ant
 * Date: 11.04.2017
 * Time: 23:35
 */

namespace Kaula\TelegramBundle\Telegram;


use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\DependencyInjection\ContainerInterface;
use unreal4u\TelegramAPI\Telegram\Types\Update;

class UserHq
{
  /**
   * @var ContainerInterface
   */
  private $container;

  /**
   * @var \Kaula\TelegramBundle\Entity\User
   */
  private $current_user;

  public function __construct(ContainerInterface $container)
  {
    $this->container = $container;
  }

  /**
   * Finds current telegram user in the database and stores the object for
   * later use.
   *
   * @param string $update_type
   * @param \unreal4u\TelegramAPI\Telegram\Types\Update $update
   * @return null|\Kaula\TelegramBundle\Entity\User
   */
  public function stashUser($update_type, Update $update)
  {
    if (Bot::UT_MESSAGE == $update_type) {
      if (!is_null($update->message)) {
        $telegram_user = $update->message->from;
      } else {
        return null;
      }
    } elseif (Bot::UT_CALLBACK_QUERY == $update_type) {
      $telegram_user = $update->callback_query->from;
    } else {
      return null;
    }

    /** @var \Kaula\TelegramBundle\Entity\User $user */
    if (is_null(
      $this->current_user = $this->getContainer()
        ->get('doctrine')
        ->getRepository('KaulaTelegramBundle:User')
        ->findOneBy(['telegram_id' => $telegram_user->id])
    )) {
      return null;
    }

    return $this->current_user;
  }

  /**
   * Returns true if current user is blocked.
   *
   * @return boolean
   */
  public function isUserBlocked()
  {
    if (is_null($this->current_user)) {
      return false;
    }

    return $this->getCurrentUser()
      ->isBlocked();
  }

  /**
   * Returns collection of Permissions for telegram user.
   *
   * @return \Doctrine\Common\Collections\Collection
   */
  public function getUserPermissions()
  {
    $permissions = new ArrayCollection();
    if (is_null($user = $this->getCurrentUser())) {
      return $permissions;
    }

    // Load roles and each role's permissions
    $roles = $user->getRoles();
    /** @var \Kaula\TelegramBundle\Entity\Role $role_item */
    foreach ($roles as $role_item) {
      $role_perms = $role_item->getPermissions();
      /** @var \Kaula\TelegramBundle\Entity\Permission $role_perm_item */
      foreach ($role_perms as $role_perm_item) {
        if (!$permissions->contains($role_perm_item)) {
          $permissions->add($role_perm_item);
        }
      }
    }

    return $permissions;
  }

  /**
   * Returns collection of Notifications for telegram user.
   *
   * @return \Doctrine\Common\Collections\Collection
   */
  public function getUserNotifications()
  {
    if (is_null($user = $this->getCurrentUser())) {
      return new ArrayCollection();
    }

    return $user->getNotifications();
  }

  /**
   * @return ContainerInterface
   */
  public function getContainer()
  {
    return $this->container;
  }

  /**
   * Get database entity for current database user.
   *
   * @return \Kaula\TelegramBundle\Entity\User
   */
  public function getCurrentUser()
  {
    return $this->current_user;
  }
}