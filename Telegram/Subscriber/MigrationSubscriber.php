<?php
/**
 * Created by PhpStorm.
 * User: ant
 * Date: 25.04.2017
 * Time: 14:19
 */

namespace Kaula\TelegramBundle\Telegram\Subscriber;


use Kaula\TelegramBundle\Telegram\Event\MigrateFromChatIdEvent;
use Kaula\TelegramBundle\Telegram\Event\MigrateToChatIdEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MigrationSubscriber extends AbstractBotSubscriber implements EventSubscriberInterface
{
  /**
   * Returns an array of event names this subscriber wants to listen to.
   *
   * The array keys are event names and the value can be:
   *
   *  * The method name to call (priority defaults to 0)
   *  * An array composed of the method name to call and the priority
   *  * An array of arrays composed of the method names to call and respective
   *    priorities, or 0 if unset
   *
   * For instance:
   *
   *  * array('eventName' => 'methodName')
   *  * array('eventName' => array('methodName', $priority))
   *  * array('eventName' => array(array('methodName1', $priority),
   * array('methodName2')))
   *
   * @return array The event names to listen to
   */
  public static function getSubscribedEvents()
  {
    return [
      MigrateToChatIdEvent::NAME   => 'onMigrateToChatId',
      MigrateFromChatIdEvent::NAME => 'onMigrateFromChatId',
    ];
  }

  /**
   * Processes migration to chat ID.
   *
   * @param MigrateToChatIdEvent $event
   */
  public function onMigrateToChatId(MigrateToChatIdEvent $event)
  {
    // Prepare Doctrine and EntityManager
    $em = $this->getDoctrine()
      ->getManager();

    // Get telegram chat ids
    $chat_from_id = $event->getMessage()->chat->id;
    $chat_to_id = $event->getMessage()->migrate_to_chat_id;

    // Find chat object. If not found, create new
    $chat = $this->getDoctrine()
      ->getRepository('KaulaTelegramBundle:Chat')
      ->findOneBy(['telegram_id' => $chat_from_id]);
    if (!$chat) {
      // Unknown chat for now. Nothing to do
      return;
    }
    $chat->setTelegramId($chat_to_id);

    // Commit changes
    $em->flush();
  }

  /**
   * Processes migration from chat ID.
   *
   * @param MigrateFromChatIdEvent $event
   */
  public function onMigrateFromChatId(MigrateFromChatIdEvent $event)
  {
    // Prepare Doctrine and EntityManager
    $em = $this->getDoctrine()
      ->getManager();

    // Get telegram chat
    $tc = $event->getMessage()->chat;

    // Find chat object. If not found, create new
    $chat = $this->getDoctrine()
      ->getRepository('KaulaTelegramBundle:Chat')
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

    // Commit changes
    $em->flush();
  }
}