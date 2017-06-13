<?php
/**
 * Created by PhpStorm.
 * User: ant
 * Date: 25.04.2017
 * Time: 14:19
 */

namespace Kaula\TelegramBundle\Telegram\Subscriber;


use Kaula\TelegramBundle\Entity\Chat;
use Kaula\TelegramBundle\Entity\ChatMember;
use Kaula\TelegramBundle\Entity\User;
use Kaula\TelegramBundle\Telegram\Event\AbstractMessageEvent;
use Kaula\TelegramBundle\Telegram\Event\JoinChatMemberEvent;
use Kaula\TelegramBundle\Telegram\Event\LeftChatMemberEvent;
use Kaula\TelegramBundle\Telegram\Event\TextReceivedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ChatMemberSubscriber extends AbstractBotSubscriber implements EventSubscriberInterface
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
      JoinChatMemberEvent::NAME => 'onMemberJoined',
      LeftChatMemberEvent::NAME => 'onMemberLeft',
      TextReceivedEvent::NAME   => 'onMemberTexted',
    ];
  }

  /**
   * When somebody joined the chat, updates database.
   *
   * @param JoinChatMemberEvent $event
   */
  public function onMemberJoined(JoinChatMemberEvent $event)
  {
    $chat = $this->prepareChat($event);
    $this->processJoinedMember($event, $chat);

    // Flush changes
    $this->getDoctrine()
      ->getManager()
      ->flush();

    // Tell the Bot this request is handled
    $this->getBot()
      ->setRequestHandled(true);
  }

  /**
   * Returns the Chat object.
   *
   * @param AbstractMessageEvent $event
   * @return \Kaula\TelegramBundle\Entity\Chat
   */
  private function prepareChat(AbstractMessageEvent $event)
  {
    // Get telegram chat object
    $tc = $event->getMessage()->chat;

    // Find chat object. If not found, create new
    $chat = $this->getDoctrine()
      ->getRepository('KaulaTelegramBundle:Chat')
      ->findOneBy(['telegram_id' => $tc->id]);
    if (!$chat) {
      $chat = new Chat();
      $this->getDoctrine()
        ->getManager()
        ->persist($chat);
    }
    $chat->setTelegramId($tc->id)
      ->setType($tc->type)
      ->setTitle($tc->title)
      ->setUsername($tc->username)
      ->setFirstName($tc->first_name)
      ->setLastName($tc->last_name)
      ->setAllMembersAreAdministrators($tc->all_members_are_administrators);

    return $chat;
  }

  /**
   * @param AbstractMessageEvent $event
   * @param Chat $chat
   */
  private function processJoinedMember(
    AbstractMessageEvent $event,
    Chat $chat
  ) {
    // User joined the group
    $tu = $event->getMessage()->new_chat_member;
    $em = $this->getDoctrine()
      ->getManager();

    // Find user object. If not found, create new
    $user = $this->getDoctrine()
      ->getRepository('KaulaTelegramBundle:User')
      ->findOneBy(['telegram_id' => $tu->id]);
    if (!$user) {
      $user = new User();
      $em->persist($user);
    }
    $user->setTelegramId($tu->id)
      ->setFirstName($tu->first_name)
      ->setLastName($tu->last_name)
      ->setUsername($tu->username);

    // Find chat member object. If not found, create new
    $chat_member = $this->getDoctrine()
      ->getRepository(
        'KaulaTelegramBundle:ChatMember'
      )
      ->findOneBy(
        [
          'chat' => $chat,
          'user' => $user,
        ]
      );
    if (!$chat_member) {
      $chat_member = new ChatMember();
      $em->persist($chat_member);
    }
    $chat_member->setChat($chat)
      ->setUser($user)
      ->setJoinDate(new \DateTime('now', new \DateTimeZone('UTC')))
      ->setLeaveDate(null)
      ->setStatus('member');
  }

  /**
   * When somebody left the chat, updates database.
   *
   * @param LeftChatMemberEvent $event
   */
  public function onMemberLeft(LeftChatMemberEvent $event)
  {
    $chat = $this->prepareChat($event);
    $this->processLeftMember($event, $chat);

    // Flush changes
    $this->getDoctrine()
      ->getManager()
      ->flush();

    // Tell the Bot this request is handled
    $this->getBot()
      ->setRequestHandled(true);
  }

  /**
   * @param AbstractMessageEvent $event
   * @param Chat $chat
   */
  private function processLeftMember(
    AbstractMessageEvent $event,
    Chat $chat
  ) {
    // User left the group
    $tu = $event->getMessage()->left_chat_member;
    $em = $this->getDoctrine()
      ->getManager();

    // Find user object. If not found, create new
    $user = $this->getDoctrine()
      ->getRepository('KaulaTelegramBundle:User')
      ->findOneBy(['telegram_id' => $tu->id]);
    if (!$user) {
      $user = new User();
      $em->persist($user);
    }
    $user->setTelegramId($tu->id)
      ->setFirstName($tu->first_name)
      ->setLastName($tu->last_name)
      ->setUsername($tu->username);

    // Find chat member object. If not found, create new
    $chat_member = $this->getDoctrine()
      ->getRepository(
        'KaulaTelegramBundle:ChatMember'
      )
      ->findOneBy(
        [
          'chat' => $chat,
          'user' => $user,
        ]
      );
    if (!$chat_member) {
      $chat_member = new ChatMember();
      $em->persist($chat_member);
    }
    $chat_member->setChat($chat)
      ->setUser($user)
      ->setLeaveDate(new \DateTime('now', new \DateTimeZone('UTC')))
      ->setStatus('left');
  }

  /**
   * When somebody posted text in the chat, updates database.
   *
   * @param TextReceivedEvent $event
   */
  public function onMemberTexted(TextReceivedEvent $event)
  {
    $chat = $this->prepareChat($event);
    $this->processTextedMember($event, $chat);

    // Flush changes
    $this->getDoctrine()
      ->getManager()
      ->flush();

    // NB: This handler SHALL NOT mark request as handled. We just update ChatMember
    // silently
  }

  /**
   * @param AbstractMessageEvent $event
   * @param Chat $chat
   */
  private function processTextedMember(
    AbstractMessageEvent $event,
    Chat $chat
  ) {
    // User left the group
    $tu = $event->getMessage()->from;
    $em = $this->getDoctrine()
      ->getManager();

    // Find user object. If not found, create new
    $user = $this->getDoctrine()
      ->getRepository('KaulaTelegramBundle:User')
      ->findOneBy(['telegram_id' => $tu->id]);
    if (!$user) {
      $user = new User();
      $em->persist($user);
    }
    $user->setTelegramId($tu->id)
      ->setFirstName($tu->first_name)
      ->setLastName($tu->last_name)
      ->setUsername($tu->username);

    // Find chat member object. If not found, create new
    $chat_member = $this->getDoctrine()
      ->getRepository(
        'KaulaTelegramBundle:ChatMember'
      )
      ->findOneBy(
        [
          'chat' => $chat,
          'user' => $user,
        ]
      );
    if (!$chat_member) {
      $chat_member = new ChatMember();
      $em->persist($chat_member);
    }
    $chat_member->setChat($chat)
      ->setUser($user)
      ->setStatus('member');
  }

}