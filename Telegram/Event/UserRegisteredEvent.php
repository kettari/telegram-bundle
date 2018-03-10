<?php
/**
 * Created by PhpStorm.
 * User: ant
 * Date: 25.04.2017
 * Time: 13:15
 */

namespace Kettari\TelegramBundle\Telegram\Event;


use Kettari\TelegramBundle\Entity\User;
use RuntimeException;
use unreal4u\TelegramAPI\Telegram\Types\Update;

class UserRegisteredEvent extends AbstractMessageEvent
{
  const NAME = 'telegram.user.registered';

  /**
   * @var \Kettari\TelegramBundle\Entity\User
   */
  private $registered_user;

  /**
   * UserRegisteredEvent constructor.
   *
   * @param Update $update
   * @param User $registered_user
   */
  public function __construct(Update $update, $registered_user)
  {
    if (is_null($update->message)) {
      throw new RuntimeException(
        'Message can\'t be null for the UserRegisteredEvent.'
      );
    }

    $this->setMessage($update->message)
      ->setUpdate($update);
    $this->registered_user = $registered_user;
  }

  /**
   * @return \Kettari\TelegramBundle\Entity\User
   */
  public function getRegisteredUser(): User
  {
    return $this->registered_user;
  }
}