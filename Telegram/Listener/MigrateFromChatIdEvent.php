<?php
/**
 * Created by PhpStorm.
 * User: ant
 * Date: 22.03.2017
 * Time: 0:21
 */

namespace Kaula\TelegramBundle\Telegram\Listener;


use Kaula\TelegramBundle\Telegram\Bot;
use unreal4u\TelegramAPI\Telegram\Types\Update;

class MigrateFromChatIdEvent implements EventListenerInterface {

  /**
   * Executes event.
   *
   * @param \Kaula\TelegramBundle\Telegram\Bot $bot
   * @param \unreal4u\TelegramAPI\Telegram\Types\Update $update
   * @return void
   */
  public function execute(Bot $bot, Update $update) {
    // Prepare Doctrine and EntityManager
    $d = $bot->getContainer()
      ->get('doctrine');
    $em = $d->getManager();

    // Get telegram chat
    $tc = $update->message->chat;

    // Find chat object. If not found, create new
    $chat = $d->getRepository('KaulaTelegramBundle:Chat')
      ->findOneBy(['telegram_id' => $tc->id]);
    if (!$chat) {
      // Unknown chat for now. Nothing to do
      return;
    }
    $chat->setType($tc->type)
      ->setTitle($tc->title)
      ->setUsername($tc->username)
      ->setFirstName($tc->first_name)
      ->setLastName($tc->last_name)
      ->setAllMembersAreAdministrators($tc->all_members_are_administrators);
    $em->persist($chat);

    // Commit changes
    $em->flush();
  }


}