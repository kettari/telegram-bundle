<?php
/**
 * Created by PhpStorm.
 * User: ant
 * Date: 11.04.2017
 * Time: 23:35
 */

namespace Kaula\TelegramBundle\Telegram;


use Doctrine\Common\Collections\ArrayCollection;
use Kaula\TelegramBundle\Entity\Role;
use Kaula\TelegramBundle\Entity\User;
use unreal4u\TelegramAPI\Telegram\Types\Update;
use unreal4u\TelegramAPI\Telegram\Types\User as TelegramUser;

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
   * Formats user name.
   *
   * @param User $userEntity
   * @param bool $redundantFormat Return both native and external names
   * @return string
   */
  public static function formatUserName($userEntity, $redundantFormat = false)
  {
    if (!is_null($userEntity)) {
      // User set
      $user_name = trim(
        $userEntity->getFirstName().' '.$userEntity->getLastName()
      );
      $external_name = trim(
        $userEntity->getExternalFirstName().' '.
        $userEntity->getExternalLastName()
      );
    } else {
      // User not set
      $user_name = 'не указан';
      $external_name = '';
    }

    if (!empty($external_name)) {

      // Return both native and external names
      if ($redundantFormat) {
        $user_name = sprintf(
          '%s (%s)',
          $user_name,
          $external_name
        );
      } else {
        $user_name = $external_name;
      }

    }

    return $user_name;
  }

  /**
   * Finds current telegram user in the database and stores the object for
   * later use.
   *
   * @param Update $update
   * @return User
   */
  public function resolveCurrentUser(Update $update)
  {
    $updateType = $this->getBot()
      ->whatUpdateType($update);
    $l = $this->getBot()
      ->getLogger();
    $l->debug(
      'About to resolve current user for update type {update_type}',
      ['update_type' => $updateType]
    );

    // Assign telegram user object depending on update type
    $telegramUser = null;
    if (Bot::UT_MESSAGE == $updateType) {
      if (!is_null($update->message)) {
        $telegramUser = $update->message->from;
      }
    } elseif (Bot::UT_CALLBACK_QUERY == $updateType) {
      if (!is_null($update->callback_query)) {
        $telegramUser = $update->callback_query->from;
      }
    }
    $l->debug(
      'Telegram user ID: {telegram_id}',
      [
        'telegram_id'   => ($telegramUser) ? $telegramUser->id : 'not resolved',
        'telegram_user' => $telegramUser,
      ]
    );
    // Check if already resolved
    if ($this->current_user) {
      $l->debug('Already resolved', ['current_user' => $this->current_user]);
      if ($telegramUser) {
        $this->updateUserEntity($this->current_user, $telegramUser);
      }

      return $this->current_user;
    }
    // Well?..
    if (is_null($telegramUser)) {
      return null;
    }

    /** @var User $user */
    if (is_null(
      $this->current_user = $this->getBot()
        ->getDoctrine()
        ->getRepository('KaulaTelegramBundle:User')
        ->findOneBy(['telegram_id' => $telegramUser->id])
    )) {
      $l->debug(
        'Telegram user not found in the database with ID={telegram_id}, creating',
        ['telegram_id' => $telegramUser->id]
      );

      // Get user entity by telegram user
      $this->current_user = $this->createUser($telegramUser);
      // Load anonymous roles and assign them to the user
      $roles = $this->getAnonymousRoles();
      $this->assignRoles($roles, $this->current_user);

      // Commit changes
      $this->getBot()
        ->getDoctrine()
        ->getManager()
        ->flush();
    }

    $l->debug(
      'Telegram user resolved to entity',
      ['user' => $this->current_user]
    );

    return $this->current_user;
  }

  /**
   * @return Bot
   */
  public function getBot(): Bot
  {
    return $this->bot;
  }

  /**
   * Updates user entity.
   *
   * @param User $user
   * @param TelegramUser $telegramUser
   */
  private function updateUserEntity($user, $telegramUser)
  {
    // Update information
    $user->setTelegramId($telegramUser->id)
      ->setFirstName($telegramUser->first_name)
      ->setLastName($telegramUser->last_name)
      ->setUsername($telegramUser->username);
  }

  /**
   * Returns the User object by telegram user.
   *
   * @param TelegramUser $telegramUser
   * @return User
   */
  private function createUser($telegramUser)
  {
    $user = new User();
    $this->getBot()
      ->getDoctrine()
      ->getManager()
      ->persist($user);
    $this->updateUserEntity($user, $telegramUser);

    return $user;
  }

  /**
   * Returns array with roles for anonymous users.
   *
   * @return array
   */
  private function getAnonymousRoles()
  {
    $roles = $this->getBot()
      ->getDoctrine()
      ->getRepository('KaulaTelegramBundle:Role')
      ->findBy(['anonymous' => true]);
    if (0 == count($roles)) {
      throw new \LogicException('Roles for guests not found');
    }

    return $roles;
  }

  /**
   * Assigns specified roles to the user.
   *
   * @param array $roles
   * @param User $user
   */
  private function assignRoles(array $roles, User $user)
  {
    /** @var Role $single_role */
    foreach ($roles as $single_role) {
      if (!$user->getRoles()
        ->contains($single_role)) {
        $user->addRole($single_role);
      }
    }
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
   * Get database entity for current database user.
   *
   * @return User
   */
  public function getCurrentUser()
  {
    return $this->current_user;
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