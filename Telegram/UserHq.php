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
  private $currentUser;

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
    if ($this->currentUser) {
      $l->debug('Already resolved', ['current_user' => $this->currentUser]);
      if ($telegramUser) {
        $this->currentUser->setTelegramId($telegramUser->id)
          ->setFirstName($telegramUser->first_name)
          ->setLastName($telegramUser->last_name)
          ->setUsername($telegramUser->username);
        $this->getBot()
          ->getDoctrine()
          ->getManager()
          ->flush();
      }

      return $this->currentUser;
    }
    // Well?..
    if (is_null($telegramUser)) {
      $l->warning('Telegram user is null, unable to resolve');

      return null;
    }

    /** @var User $user */
    if (is_null(
      $this->currentUser = $this->getBot()
        ->getDoctrine()
        ->getRepository('KaulaTelegramBundle:User')
        ->findByTelegramId($telegramUser->id)
    )) {
      // Get user entity by telegram user
      $this->currentUser = $this->createAnonymousUser($telegramUser);
    }

    $l->debug(
      'Telegram user resolved to entity',
      ['user' => $this->currentUser]
    );

    return $this->currentUser;
  }

  /**
   * @return Bot
   */
  public function getBot(): Bot
  {
    return $this->bot;
  }

  /**
   * Creates user in the database. Assigns anonymous roles.
   *
   * @param \unreal4u\TelegramAPI\Telegram\Types\User $tu
   * @return \Kaula\TelegramBundle\Entity\User
   */
  public function createAnonymousUser(TelegramUser $tu)
  {
    $l = $this->getBot()
      ->getLogger();
    $l->debug(
      'About to create anonymous user for TelegramID={telegram_id}',
      ['telegram_id' => $tu->id, 'telegram_user' => $tu]
    );
    $d = $this->getBot()
      ->getDoctrine();
    $em = $d->getManager();

    // Create user entity and assign roles
    $user = new User();
    $user->setTelegramId($tu->id)
      ->setFirstName($tu->first_name)
      ->setLastName($tu->last_name)
      ->setUsername($tu->username);
    // Get roles and assign them
    $roles = $this->getAnonymousRoles();
    $l->debug(
      'Assigning {roles_count} role(s) to the user',
      ['roles_count' => count($roles), 'roles' => $roles]
    );
    $this->assignRoles($roles, $user);

    // Commit changes
    $em->persist($user);
    $em->flush();

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
    /** @var Role $roleItem */
    foreach ($roles as $roleItem) {
      if (!$user->getRoles()
        ->contains($roleItem)) {
        $user->addRole($roleItem);
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
    if (is_null($this->currentUser)) {
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
    return $this->currentUser;
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
    /** @var \Kaula\TelegramBundle\Entity\Role $roleItem */
    foreach ($roles as $roleItem) {
      $rolePerms = $roleItem->getPermissions();
      /** @var \Kaula\TelegramBundle\Entity\Permission $rolePermItem */
      foreach ($rolePerms as $rolePermItem) {
        if (!$permissions->contains($rolePermItem)) {
          $permissions->add($rolePermItem);
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
   * @param TelegramUser $telegramUser
   * @return \Kaula\TelegramBundle\Entity\User|null
   */
  public function getEntityByTelegram($telegramUser)
  {
    /** @var User $user */
    return $this->getBot()
      ->getDoctrine()
      ->getRepository('KaulaTelegramBundle:User')
      ->findByTelegramId($telegramUser->id);
  }

}