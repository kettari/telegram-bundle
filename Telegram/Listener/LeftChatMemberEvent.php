<?php
/**
 * Created by PhpStorm.
 * User: ant
 * Date: 21.03.2017
 * Time: 21:14
 */

namespace Kaula\TelegramBundle\Telegram\Listener;



use Kaula\TelegramBundle\Entity\ChatMember;
use Kaula\TelegramBundle\Entity\User;
use Kaula\TelegramBundle\Telegram\Bot;
use unreal4u\TelegramAPI\Telegram\Types\Update;

class LeftChatMemberEvent extends AbstractChatMemberEvent {

  /**
   * @param \Kaula\TelegramBundle\Telegram\Bot $bot
   * @param \unreal4u\TelegramAPI\Telegram\Types\Update $update
   * @return mixed|void
   */
  protected function executeEvent(Bot $bot, Update $update) {
    // User left the group
    $tu = $update->message->left_chat_member;

    // Find user object. If not found, create new
    $this->user = $this->doctrine->getRepository('KaulaTelegramBundle:User')
      ->findOneBy(['telegram_id' => $tu->id]);
    if (!$this->user) {
      $this->user = new User();
    }
    $this->user->setTelegramId($tu->id)
      ->setFirstName($tu->first_name)
      ->setLastName($tu->last_name)
      ->setUsername($tu->username);
    $this->entity_manager->persist($this->user);

    // Find chat member object. If not found, create new
    $chat_member = $this->doctrine->getRepository('KaulaTelegramBundle:ChatMember')
      ->findOneBy([
        'chat' => $this->chat->getId(),
        'user' => $this->user->getId(),
      ]);
    if (!$chat_member) {
      $chat_member = new ChatMember();
    }
    $chat_member->setChat($this->chat)
      ->setUser($this->user)
      ->setLeaveDate(new \DateTime())
      ->setStatus('left');
    $this->entity_manager->persist($chat_member);
  }

}