<?php
/**
 * Created by PhpStorm.
 * User: ant
 * Date: 11.04.2017
 * Time: 23:35
 */

namespace Kaula\TelegramBundle\Telegram;



use Doctrine\Common\Collections\ArrayCollection;
use Kaula\TelegramBundle\Entity\User;
use unreal4u\TelegramAPI\Telegram\Types\Update;

class UserHq
{
  /**
   * @var Bot
   */
  private $bot;

  /**
   * @var User
   */
  private $current_user;

  /**
   * UserHq constructor.
   *
   * @param Bot $bot
   */
  public function __construct(Bot $bot)
  {
    $this->bot = $bot;
  }

  /**
   * Finds current telegram user in the database and stores the object for
   * later use.
   *
   * @param Update $update
   * @return null|User
   */
  public function resolveCurrentUser(Update $update)
  {
    $update_type = $this->getBot()
      ->whatUpdateType($update);

    // Assign telegram user object depending on update type
    $telegram_user = null;
    if (Bot::UT_MESSAGE == $update_type) {
      if (!is_null($update->message)) {
        $telegram_user = $update->message->from;
      }
    } elseif (Bot::UT_CALLBACK_QUERY == $update_type) {
      if (!is_null($update->callback_query)) {
        $telegram_user = $update->callback_query->from;
      }
    }
    // Well?..
    if (is_null($telegram_user)) {
      return null;
    }

    /** @var User $user */
    if (is_null(
      $this->current_user = $this->getBot()
        ->getDoctrine()
        ->getRepository('KaulaTelegramBundle:User')
        ->findOneBy(['telegram_id' => $telegram_user->id])
    )) {
      return null;
    }

    return $this->getCurrentUser();
  }

  /**
   * @return Bot
   */
  public function getBot(): Bot
  {
    return $this->bot;
  }

  /**
   * Get database entity for current database user.
   *
   * @return User
   */
  public function getCurrentUser()
  {
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
   * Returns database User entity by telegram User object.
   *
   * @param \unreal4u\TelegramAPI\Telegram\Types\User $telegram_user
   * @return \Kaula\TelegramBundle\Entity\User|null
   */
  public function getEntityByTelegram($telegram_user)
  {
    /** @var User $user */
    return $this->getBot()
        ->getDoctrine()
        ->getRepository('KaulaTelegramBundle:User')
        ->findOneBy(['telegram_id' => $telegram_user->id]);
  }


}