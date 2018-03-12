<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram;


use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Kettari\TelegramBundle\Entity\Role;
use Kettari\TelegramBundle\Entity\User;
use Kettari\TelegramBundle\Exception\CurrentUserNotDefinedException;
use Kettari\TelegramBundle\Exception\TelegramBundleException;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;
use unreal4u\TelegramAPI\Telegram\Types\Update;
use unreal4u\TelegramAPI\Telegram\Types\User as TelegramUser;

class UserHq implements UserHqInterface
{
  /**
   * @var LoggerInterface
   */
  private $logger;

  /**
   * @var Registry
   */
  private $doctrine;

  /**
   * @var User
   */
  private $currentUser;

  /**
   * UserHq constructor.
   *
   * @param \Psr\Log\LoggerInterface $logger
   * @param \Symfony\Bridge\Doctrine\RegistryInterface $doctrine
   */
  public function __construct(
    LoggerInterface $logger,
    RegistryInterface $doctrine
  ) {
    $this->logger = $logger;
    $this->doctrine = $doctrine;
  }

  /**
   * Formats user name.
   *
   * @param User $userEntity
   * @param bool $redundantFormat Return both native and external names
   * @return string
   * @deprecated To be removed
   */
  public static function formatUserName($userEntity, $redundantFormat = false)
  {
    if (!is_null($userEntity)) {
      // User set
      $userName = trim(
        $userEntity->getFirstName().' '.$userEntity->getLastName()
      );
      $externalName = trim(
        $userEntity->getExternalFirstName().' '.
        $userEntity->getExternalLastName()
      );
    } else {
      // User not set
      $userName = 'не указан';
      $externalName = '';
    }

    if (!empty($externalName)) {

      // Return both native and external names
      if ($redundantFormat) {
        $userName = sprintf(
          '%s (%s)',
          $userName,
          $externalName
        );
      } else {
        $userName = $externalName;
      }

    }

    return $userName;
  }

  /**
   * {@inheritdoc}
   */
  public function resolveCurrentUser(Update $update): User
  {
    $updateType = UpdateTypeResolver::getUpdateType($update);
    $this->logger->debug(
      'About to resolve current user for the update type {update_type}',
      ['update_type' => $updateType]
    );

    // Assign telegram user object depending on update type
    $telegramUser = null;
    if (UpdateTypeResolver::UT_MESSAGE == $updateType) {
      if (!is_null($update->message)) {
        $telegramUser = $update->message->from;
      }
    } elseif (UpdateTypeResolver::UT_CALLBACK_QUERY == $updateType) {
      if (!is_null($update->callback_query)) {
        $telegramUser = $update->callback_query->from;
      }
    }
    // Can't go further without proper user resolving
    if (is_null($telegramUser)) {
      throw new TelegramBundleException('Telegram user object not found in the update object.');
    }

    $this->logger->debug(
      'Telegram user ID={telegram_id}',
      [
        'telegram_id'   => $telegramUser->id,
        'telegram_user' => $telegramUser,
      ]
    );
    // Check if already resolved
    if ($this->currentUser) {
      $this->logger->debug(
        'Already resolved',
        ['current_user' => $this->currentUser]
      );
      $this->currentUser->setTelegramId($telegramUser->id)
        ->setFirstName($telegramUser->first_name)
        ->setLastName($telegramUser->last_name)
        ->setUsername($telegramUser->username);
      $this->doctrine->getManager()
        ->flush();

      return $this->currentUser;
    }

    /** @var User $user */
    if (is_null(
      $this->currentUser = $this->doctrine->getRepository(
        'KettariTelegramBundle:User'
      )
        ->findOneByTelegramId($telegramUser->id)
    )) {
      // Get user entity by telegram user
      $this->currentUser = $this->createAnonymousUser($telegramUser);
    }

    $this->logger->debug(
      'Telegram user successfully resolved to entity ID={user_id}',
      ['user_id' => $this->currentUser->getId(), 'user' => $this->currentUser]
    );

    return $this->currentUser;
  }

  /**
   * Creates user in the database. Assigns anonymous roles.
   *
   * @param \unreal4u\TelegramAPI\Telegram\Types\User $tu
   * @return \Kettari\TelegramBundle\Entity\User
   */
  private function createAnonymousUser(TelegramUser $tu)
  {
    $this->logger->debug(
      'About to create anonymous user for TelegramID={telegram_id}',
      ['telegram_id' => $tu->id, 'telegram_user' => $tu]
    );

    // Create user entity and assign roles
    $user = new User();
    $user->setTelegramId($tu->id)
      ->setFirstName($tu->first_name)
      ->setLastName($tu->last_name)
      ->setUsername($tu->username);
    // Get roles and assign them
    $roles = $this->getAnonymousRoles();
    $this->logger->debug(
      'Assigning {roles_count} role(s) to the user',
      ['roles_count' => count($roles), 'roles' => $roles]
    );
    $this->assignRoles($roles, $user);

    // Commit changes
    $em = $this->doctrine->getManager();
    $em->persist($user);
    $em->flush();

    $this->logger->debug(
      'Created anonymous user for TelegramID={telegram_id}, entity ID={user_id}',
      [
        'telegram_id'   => $tu->id,
        'telegram_user' => $tu,
        'user_id'       => $user->getId(),
      ]
    );

    return $user;
  }

  /**
   * Returns array with roles for anonymous users.
   *
   * @return array
   */
  private function getAnonymousRoles()
  {
    $roles = $this->doctrine->getRepository('KettariTelegramBundle:Role')
      ->findAnonymous();
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
   * {@inheritdoc}
   */
  public function isUserBlocked(): bool
  {
    if (is_null($this->currentUser)) {
      throw new CurrentUserNotDefinedException('Unable to tell if user is blocked. Current user not resolved.');
    }

    return $this->getCurrentUser()
      ->isBlocked();
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrentUser()
  {
    return $this->currentUser;
  }

  /**
   * {@inheritdoc}
   */
  public function getUserPermissions(): Collection
  {
    if (is_null($this->currentUser)) {
      throw new CurrentUserNotDefinedException('Unable to get user permissions. Current user not resolved.');
    }

    $permissions = new ArrayCollection();
    // Load roles and each role's permissions
    $roles = $this->currentUser->getRoles();
    /** @var \Kettari\TelegramBundle\Entity\Role $roleItem */
    foreach ($roles as $roleItem) {
      $rolePerms = $roleItem->getPermissions();
      /** @var \Kettari\TelegramBundle\Entity\Permission $rolePermItem */
      foreach ($rolePerms as $rolePermItem) {
        if (!$permissions->contains($rolePermItem)) {
          $permissions->add($rolePermItem);
        }
      }
    }

    return $permissions;
  }

  /**
   * {@inheritdoc}
   */
  public function getUserNotifications(): Collection
  {
    if (is_null($this->currentUser)) {
      throw new TelegramBundleException('Unable to get user notifications. Current user not resolved.');
    }

    return $this->currentUser->getNotifications();
  }

}