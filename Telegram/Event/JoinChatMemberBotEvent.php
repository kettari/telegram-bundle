<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram\Event;


use Kettari\TelegramBundle\Entity\Chat;
use Kettari\TelegramBundle\Entity\User;
use unreal4u\TelegramAPI\Telegram\Types\Update;
use unreal4u\TelegramAPI\Telegram\Types\User as TelegramUser;

class JoinChatMemberBotEvent extends AbstractMessageEvent
{
  const NAME = 'telegram.chatmember.bot_joined';

  /**
   * @var \unreal4u\TelegramAPI\Telegram\Types\User
   */
  private $joinedUser;

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
   * @param \unreal4u\TelegramAPI\Telegram\Types\User $joinedUser
   * @param \Kettari\TelegramBundle\Entity\Chat
   * @param \Kettari\TelegramBundle\Entity\User
   */
  public function __construct(
    Update $update,
    TelegramUser $joinedUser,
    Chat $chatEntity,
    User $userEntity
  ) {
    parent::__construct($update);
    $this->joinedUser = $joinedUser;
    $this->chatEntity = $chatEntity;
    $this->userEntity = $userEntity;
  }

  /**
   * @return \unreal4u\TelegramAPI\Telegram\Types\User
   */
  public function getJoinedUser(): TelegramUser
  {
    return $this->joinedUser;
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