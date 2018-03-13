<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram\Event;


use Kettari\TelegramBundle\Entity\Chat;
use unreal4u\TelegramAPI\Telegram\Types\Update;
use unreal4u\TelegramAPI\Telegram\Types\User as TelegramUser;

class JoinChatMemberEvent extends AbstractMessageEvent
{
  const NAME = 'telegram.chatmember.joined';

  /**
   * @var \unreal4u\TelegramAPI\Telegram\Types\User
   */
  private $joinedUser;

  /**
   * @var \Kettari\TelegramBundle\Entity\Chat
   */
  private $chatEntity;

  /**
   * JoinChatMemberEvent constructor.
   *
   * @param Update $update
   * @param \unreal4u\TelegramAPI\Telegram\Types\User $joinedUser
   * @param \Kettari\TelegramBundle\Entity\Chat $chatEntity
   */
  public function __construct(
    Update $update,
    TelegramUser $joinedUser,
    Chat $chatEntity
  ) {
    parent::__construct($update);
    $this->joinedUser = $joinedUser;
    $this->chatEntity = $chatEntity;
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
}