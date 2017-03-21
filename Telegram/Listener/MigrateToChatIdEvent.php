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

class MigrateToChatIdEvent implements EventListenerInterface {

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

    // Get telegram chat ids
    $chat_from_id = $update->message->chat->id;
    $chat_to_id = $update->message->migrate_to_chat_id;

    // Find chat object. If not found, create new
    $chat = $d->getRepository('KaulaTelegramBundle:Chat')
      ->findOneBy(['telegram_id' => $chat_from_id]);
    if (!$chat) {
      // Unknown chat for now. Nothing to do
      return;
    }
    $chat->setTelegramId($chat_to_id);
    $em->persist($chat);

    // Commit changes
    $em->flush();
  }


}