<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram;


use Kettari\TelegramBundle\Entity\User;

trait UserHelperTrait
{
  /**
   * Formats user name.
   *
   * @param User $user
   * @return string
   */
  public static function formatUserName(User $user)
  {
    $userName = trim(
      $user->getFirstName().' '.$user->getLastName()
    );
    $externalName = trim(
      $user->getExternalFirstName().' '.$user->getExternalLastName()
    );

    if (!empty($externalName)) {
      $userName = $externalName;
    }

    return $userName;
  }
}