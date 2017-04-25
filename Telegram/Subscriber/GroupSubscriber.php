<?php
/**
 * Created by PhpStorm.
 * User: ant
 * Date: 25.04.2017
 * Time: 14:19
 */

namespace Kaula\TelegramBundle\Telegram\Subscriber;


use Kaula\TelegramBundle\Entity\Chat;
use Kaula\TelegramBundle\Telegram\Event\GroupCreatedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use unreal4u\TelegramAPI\Telegram\Types\Update;

class GroupSubscriber extends AbstractBotSubscriber implements EventSubscriberInterface
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
    return [GroupCreatedEvent::NAME => 'onGroupCreated'];
  }

  /**
   * Handles group creation.
   *
   * @param GroupCreatedEvent $event
   */
  public function onGroupCreated(GroupCreatedEvent $event)
  {
    $this->processGroupCreation($event->getUpdate());
  }

  /**
   * Processes group creation.
   *
   * @param Update $update
   */
  private function processGroupCreation(Update $update)
  {
    // Prepare Doctrine and EntityManager
    $em = $this->getDoctrine()
      ->getManager();

    // Get telegram chat
    $tc = $update->message->chat;

    // Find chat object. If not found, create new
    $chat = $this->getDoctrine()
      ->getRepository('KaulaTelegramBundle:Chat')
      ->findOneBy(['telegram_id' => $tc->id]);
    if (!$chat) {
      $chat = new Chat();
      $em->persist($chat);
    }
    $chat->setTelegramId($tc->id)
      ->setType($tc->type)
      ->setTitle($tc->title)
      ->setUsername($tc->username)
      ->setFirstName($tc->first_name)
      ->setLastName($tc->last_name)
      ->setAllMembersAreAdministrators($tc->all_members_are_administrators);

    // Commit changes
    $em->flush();
  }

}