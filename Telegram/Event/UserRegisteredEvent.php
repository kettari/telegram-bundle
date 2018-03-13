<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram\Event;


use Kettari\TelegramBundle\Entity\User;
use unreal4u\TelegramAPI\Telegram\Types\Update;

class UserRegisteredEvent extends AbstractMessageEvent
{
  const NAME = 'telegram.user.registered';

  /**
   * @var \Kettari\TelegramBundle\Entity\User
   */
  private $registeredUser;

  /**
   * UserRegisteredEvent constructor.
   *
   * @param Update $update
   * @param User $registeredUser
   */
  public function __construct(Update $update, User $registeredUser)
  {
    parent::__construct($update);
    $this->registeredUser = $registeredUser;
  }

  /**
   * @return \Kettari\TelegramBundle\Entity\User
   */
  public function getRegisteredUser(): User
  {
    return $this->registeredUser;
  }
}