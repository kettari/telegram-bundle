<?php
/**
 * Created by PhpStorm.
 * User: ant
 * Date: 12.03.2018
 * Time: 20:05
 */

namespace Kettari\TelegramBundle\Telegram;


use Doctrine\Common\Collections\Collection;
use Kettari\TelegramBundle\Entity\User;
use unreal4u\TelegramAPI\Telegram\Types\Update;
use unreal4u\TelegramAPI\Telegram\Types\User as TelegramUser;

interface UserHqInterface
{
  /**
   * Finds current telegram user in the database and stores the object for
   * later use.
   *
   * @param Update $update
   * @return User
   */
  public function resolveCurrentUser(Update $update): User;

  /**
   * Creates user in the database. Assigns anonymous roles.
   *
   * @param \unreal4u\TelegramAPI\Telegram\Types\User $telegramUser
   * @return \Kettari\TelegramBundle\Entity\User
   */
  public function createAnonymousUser(TelegramUser $telegramUser): User;

  /**
   * Returns true if current user is blocked.
   *
   * @throws \Kettari\TelegramBundle\Exception\TelegramBundleException
   * @return boolean
   */
  public function isUserBlocked(): bool;

  /**
   * Get database entity for current user.
   *
   * @return null|User
   */
  public function getCurrentUser();

  /**
   * Returns collection of Permissions for current telegram user.
   *
   * @return \Doctrine\Common\Collections\Collection
   */
  public function getUserPermissions(): Collection;

  /**
   * Returns collection of Notifications for telegram user.
   *
   * @return \Doctrine\Common\Collections\Collection
   */
  public function getUserNotifications(): Collection;
}