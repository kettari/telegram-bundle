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

class LeftChatMemberBotEvent extends AbstractMessageEvent
{
  const NAME = 'telegram.chatmember.bot_left';

  /**
   * @var \unreal4u\TelegramAPI\Telegram\Types\User
   */
  private $left_user;

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
   * @param \unreal4u\TelegramAPI\Telegram\Types\User $left_user
   * @param \Kaula\TelegramBundle\Entity\Chat
   * @param \Kaula\TelegramBundle\Entity\User
   */
  public function __construct(Update $update, $left_user, $chat_entity, $user_entity)
  {
    if (is_null($update->message)) {
      throw new RuntimeException(
        'Message can\'t be null for the LeftChatMemberBotEvent.'
      );
    }

    $this->setMessage($update->message)
      ->setUpdate($update);
    $this->left_user = $left_user;
    $this->chat_entity = $chat_entity;
    $this->user_entity = $user_entity;
  }

  /**
   * @return \unreal4u\TelegramAPI\Telegram\Types\User
   */
  public function getLeftUser()
  {
    return $this->left_user;
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