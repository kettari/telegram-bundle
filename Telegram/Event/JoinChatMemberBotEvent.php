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

class JoinChatMemberBotEvent extends AbstractMessageEvent
{
  const NAME = 'telegram.chatmember.bot_joined';

  /**
   * @var \unreal4u\TelegramAPI\Telegram\Types\User
   */
  private $joined_user;

  /**
   * @var \Kaula\TelegramBundle\Entity\Chat
   */
  private $chat_entity;

  /**
   * @var \Kaula\TelegramBundle\Entity\User
   */
  private $user_entity;

  /**
   * JoinChatMemberEvent constructor.
   *
   * @param Update $update
   * @param \unreal4u\TelegramAPI\Telegram\Types\User $joined_user
   * @param \Kaula\TelegramBundle\Entity\Chat
   * @param \Kaula\TelegramBundle\Entity\User
   */
  public function __construct(Update $update, $joined_user, $chat_entity, $user_entity)
  {
    if (is_null($update->message)) {
      throw new RuntimeException(
        'Message can\'t be null for the JoinChatMemberBotEvent.'
      );
    }

    $this->setMessage($update->message)
      ->setUpdate($update);
    $this->joined_user = $joined_user;
    $this->chat_entity = $chat_entity;
    $this->user_entity = $user_entity;
  }

  /**
   * @return \unreal4u\TelegramAPI\Telegram\Types\User
   */
  public function getJoinedUser()
  {
    return $this->joined_user;
  }

  /**
   * @return \Kaula\TelegramBundle\Entity\Chat
   */
  public function getChatEntity()
  {
    return $this->chat_entity;
  }

  /**
   * @return \Kaula\TelegramBundle\Entity\User
   */
  public function getUserEntity()
  {
    return $this->user_entity;
  }

}