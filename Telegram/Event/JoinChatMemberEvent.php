<?php
/**
 * Created by PhpStorm.
 * User: ant
 * Date: 25.04.2017
 * Time: 19:03
 */

namespace Kettari\TelegramBundle\Telegram\Event;


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
   * @var \Kettari\TelegramBundle\Entity\Chat
   */
  private $chat_entity;

  /**
   * JoinChatMemberEvent constructor.
   *
   * @param Update $update
   * @param \unreal4u\TelegramAPI\Telegram\Types\User $joined_user
   * @param \Kettari\TelegramBundle\Entity\Chat $chat_entity
   */
  public function __construct(Update $update, $joined_user, $chat_entity)
  {
    if (is_null($update->message)) {
      throw new RuntimeException(
        'Message can\'t be null for the JoinChatMemberEvent.'
      );
    }

    $this->setMessage($update->message)
      ->setUpdate($update);
    $this->joined_user = $joined_user;
    $this->chat_entity = $chat_entity;
  }

  /**
   * @return \unreal4u\TelegramAPI\Telegram\Types\User
   */
  public function getJoinedUser()
  {
    return $this->joined_user;
  }

  /**
   * @return \Kettari\TelegramBundle\Entity\Chat
   */
  public function getChatEntity()
  {
    return $this->chat_entity;
  }
}