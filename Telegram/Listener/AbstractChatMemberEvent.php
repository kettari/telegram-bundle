<?php
/**
 * Created by PhpStorm.
 * User: ant
 * Date: 21.03.2017
 * Time: 21:14
 */

namespace Kaula\TelegramBundle\Telegram\Listener;



use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;

use Kaula\TelegramBundle\Entity\Chat;

use Kaula\TelegramBundle\Entity\User;
use Kaula\TelegramBundle\Telegram\Bot;
use unreal4u\TelegramAPI\Telegram\Types\Update;

abstract class AbstractChatMemberEvent implements EventListenerInterface {

  /**
   * @var Registry
   */
  protected $doctrine;

  /**
   * @var EntityManager
   */
  protected $entity_manager;

  /**
   * @var Chat
   */
  protected $chat;

  /**
   * @var User
   */
  protected $user;


  /**
   * @param \Kaula\TelegramBundle\Telegram\Bot $bot
   * @param \unreal4u\TelegramAPI\Telegram\Types\Update $update
   */
  public function execute(Bot $bot, Update $update) {
    // Prepare Doctrine and EntityManager
    $this->doctrine = $bot->getContainer()
      ->get('doctrine');
    $this->entity_manager = $this->doctrine->getManager();

    // Get telegram chat object
    $tc = $update->message->chat;

    // Find chat object. If not found, create new
    $this->chat = $this->doctrine->getRepository('KaulaTelegramBundle:Chat')
      ->findOneBy(['telegram_id' => $tc->id]);
    if (!$this->chat) {
      $this->chat = new Chat();
    }
    $this->chat->setTelegramId($tc->id)
      ->setType($tc->type)
      ->setTitle($tc->title)
      ->setUsername($tc->username)
      ->setFirstName($tc->first_name)
      ->setLastName($tc->last_name)
      ->setAllMembersAreAdministrators($tc->all_members_are_administrators);
    $this->entity_manager->persist($this->chat);

    // Execute chat member operations
    $this->executeEvent($bot, $update);

    // Commit changes
    $this->entity_manager->flush();
  }

  /**
   * Process chat member code.
   *
   * @param \Kaula\TelegramBundle\Telegram\Bot $bot
   * @param \unreal4u\TelegramAPI\Telegram\Types\Update $update
   * @return mixed
   */
  abstract protected function executeEvent(Bot $bot, Update $update);

}