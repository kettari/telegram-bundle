<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram\Event;

use Kettari\TelegramBundle\Entity\Chat;
use Kettari\TelegramBundle\Entity\User;
use unreal4u\TelegramAPI\Telegram\Types\Update;
use unreal4u\TelegramAPI\Telegram\Types\User as TelegramUser;

class LeftChatMemberBotEvent extends AbstractMessageEvent
{
  const NAME = 'telegram.chatmember.bot_left';

  /**
   * @var \unreal4u\TelegramAPI\Telegram\Types\User
   */
  private $leftUser;

  /**
   * @var \Kettari\TelegramBundle\Entity\Chat
   */
  private $chatEntity;

  /**
   * @var \Kettari\TelegramBundle\Entity\User
   */
  private $userEntity;

  /**
   * JoinChatMemberEvent constructor.
   *
   * @param Update $update
   * @param \unreal4u\TelegramAPI\Telegram\Types\User $leftUser
   * @param \Kettari\TelegramBundle\Entity\Chat
   * @param \Kettari\TelegramBundle\Entity\User
   */
  public function __construct(
    Update $update,
    TelegramUser $leftUser,
    Chat $chatEntity,
    User $userEntity
  ) {
    parent::__construct($update);

    $this->leftUser = $leftUser;
    $this->chatEntity = $chatEntity;
    $this->userEntity = $userEntity;
  }

  /**
   * @return \unreal4u\TelegramAPI\Telegram\Types\User
   */
  public function getLeftUser(): TelegramUser
  {
    return $this->leftUser;
  }

  /**
   * @return \Kettari\TelegramBundle\Entity\Chat
   */
  public function getChatEntity(): Chat
  {
    return $this->chatEntity;
  }

  /**
   * @return \Kettari\TelegramBundle\Entity\User
   */
  public function getUserEntity(): User
  {
    return $this->userEntity;
  }

}