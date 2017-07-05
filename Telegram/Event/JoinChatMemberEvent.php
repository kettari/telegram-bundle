<?php
/**
 * Created by PhpStorm.
 * User: ant
 * Date: 25.04.2017
 * Time: 19:03
 */

namespace Kaula\TelegramBundle\Telegram\Event;


use RuntimeException;
use unreal4u\TelegramAPI\Telegram\Types\Update;

class JoinChatMemberEvent extends AbstractMessageEvent
{
  const NAME = 'telegram.chatmember.joined';

  /**
   * @var \unreal4u\TelegramAPI\Telegram\Types\User
   */
  private $joined_user;

  /**
   * JoinChatMemberEvent constructor.
   *
   * @param Update $update
   */
  public function __construct(Update $update)
  {
    if (is_null($update->message)) {
      throw new RuntimeException(
        'Message can\'t be null for the JoinChatMemberEvent.'
      );
    }

    $this->setMessage($update->message)
      ->setUpdate($update);
  }

  /**
   * @return \unreal4u\TelegramAPI\Telegram\Types\User
   */
  public function getJoinedUser()
  {
    return $this->joined_user;
  }

  /**
   * @param \unreal4u\TelegramAPI\Telegram\Types\User $joined_user
   */
  public function setJoinedUser($joined_user)
  {
    $this->joined_user = $joined_user;
  }
}