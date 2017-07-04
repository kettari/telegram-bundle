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
use Kaula\TelegramBundle\Telegram\Event\JoinChatMemberEvent;
use Kaula\TelegramBundle\Telegram\Event\JoinChatMembersManyEvent;
use Kaula\TelegramBundle\Telegram\Event\LeftChatMemberEvent;
use Kaula\TelegramBundle\Telegram\Event\TextReceivedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use unreal4u\TelegramAPI\Telegram\Types\Chat as TelegramChat;
use unreal4u\TelegramAPI\Telegram\Types\User as TelegramUser;

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
      //JoinChatMemberEvent::NAME => ['onMemberJoined', -90000],
      JoinChatMembersManyEvent::NAME => ['onMembersManyJoined', -90000],
      LeftChatMemberEvent::NAME      => ['onMemberLeft', -90000],
      TextReceivedEvent::NAME        => 'onMemberTexted',
    ];
  }

  /**
   * When somebody joined the chat, updates database.
   *
   * @param JoinChatMemberEvent $event
   */
  /*public function onMemberJoined(JoinChatMemberEvent $event)
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
  }*/

  /**
   * When somebody joined the chat, updates database.
   *
   * @param JoinChatMembersManyEvent $event
   */
  public function onMembersManyJoined(JoinChatMembersManyEvent $event)
  {
    $chat = $this->prepareChat($event->getMessage()->chat);
    $users = $event->getMessage()->new_chat_members;
    /** @var TelegramUser $joined_user */
    foreach ($users as $joined_user) {
      $this->processJoinedMember($joined_user, $chat);
    }

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
   * @param \unreal4u\TelegramAPI\Telegram\Types\Chat $tc
   * @return \Kaula\TelegramBundle\Entity\Chat
   */
  private function prepareChat(TelegramChat $tc)
  {
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
   * @param \unreal4u\TelegramAPI\Telegram\Types\User $tu
   * @param Chat $chat
   */
  private function processJoinedMember(
    TelegramUser $tu,
    Chat $chat
  ) {
    $em = $this->getDoctrine()
      ->getManager();

    // Find user object. If not found, create new
    $user_entity = $this->getDoctrine()
      ->getRepository('KaulaTelegramBundle:User')
      ->findOneBy(['telegram_id' => $tu->id]);
    if (!$user_entity) {
      $user_entity = new User();
      $em->persist($user_entity);
    }
    $user_entity->setTelegramId($tu->id)
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
          'user' => $user_entity,
        ]
      );
    if (!$chat_member) {
      $chat_member = new ChatMember();
      $em->persist($chat_member);
    }
    $chat_member->setChat($chat)
      ->setUser($user_entity)
      ->setJoinDate(new \DateTime('now', new \DateTimeZone('UTC')))
      ->setLeaveDate(null)
      ->setStatus('member');

    // Log event
    $external_name = trim($user_entity->getFirstName().' '.$user_entity->getLastName());
    if (empty($external_name)) {
      $external_name = '(no external name)';
    }
    $chat_name = trim($chat->getTitle());
    if (empty($chat_name)) {
      $chat_name = trim($chat->getFirstName().' '.$chat->getLastName());
    }
    $this->getBot()
      ->getLogger()
      ->info(
        'User "{telegram_user_name}" ("{external_name}") joined the chat "{chat_name}" ({chat_id})',
        [
          'telegram_user_name' => trim($tu->first_name.' '.$tu->last_name),
          'external_name'      => $external_name,
          'chat_name'          => $chat_name,
          'chat_id'            => $chat->getId(),
        ]
      );
  }

  /**
   * When somebody left the chat, updates database.
   *
   * @param LeftChatMemberEvent $event
   */
  public function onMemberLeft(LeftChatMemberEvent $event)
  {
    $chat = $this->prepareChat($event->getMessage()->chat);
    $this->processLeftMember($event->getMessage()->left_chat_member, $chat);

    // Flush changes
    $this->getDoctrine()
      ->getManager()
      ->flush();

    // Tell the Bot this request is handled
    $this->getBot()
      ->setRequestHandled(true);
  }

  /**
   * @param \unreal4u\TelegramAPI\Telegram\Types\User $tu
   * @param Chat $chat
   */
  private function processLeftMember(
    TelegramUser $tu,
    Chat $chat
  ) {
    $em = $this->getDoctrine()
      ->getManager();

    // Find user object. If not found, create new
    $user_entity = $this->getDoctrine()
      ->getRepository('KaulaTelegramBundle:User')
      ->findOneBy(['telegram_id' => $tu->id]);
    if (!$user_entity) {
      $user_entity = new User();
      $em->persist($user_entity);
    }
    $user_entity->setTelegramId($tu->id)
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
          'user' => $user_entity,
        ]
      );
    if (!$chat_member) {
      $chat_member = new ChatMember();
      $em->persist($chat_member);
    }
    $chat_member->setChat($chat)
      ->setUser($user_entity)
      ->setLeaveDate(new \DateTime('now', new \DateTimeZone('UTC')))
      ->setStatus('left');

    // Log event
    $external_name = trim($user_entity->getFirstName().' '.$user_entity->getLastName());
    if (empty($external_name)) {
      $external_name = '(no external name)';
    }
    $chat_name = trim($chat->getTitle());
    if (empty($chat_name)) {
      $chat_name = trim($chat->getFirstName().' '.$chat->getLastName());
    }
    $this->getBot()
      ->getLogger()
      ->info(
        'User "{telegram_user_name}" ("{external_name}") left the chat "{chat_name}" ({chat_id})',
        [
          'telegram_user_name' => trim($tu->first_name.' '.$tu->last_name),
          'external_name'      => $external_name,
          'chat_name'          => $chat_name,
          'chat_id'            => $chat->getId(),
        ]
      );
  }

  /**
   * When somebody posted text in the chat, updates database.
   *
   * @param TextReceivedEvent $event
   */
  public function onMemberTexted(TextReceivedEvent $event)
  {
    $chat = $this->prepareChat($event->getMessage()->chat);
    $this->processTextedMember($event->getMessage()->from, $chat);

    // Flush changes
    $this->getDoctrine()
      ->getManager()
      ->flush();

    // NB: This handler SHALL NOT mark request as handled. We just update ChatMember
    // silently
  }

  /**
   * @param \unreal4u\TelegramAPI\Telegram\Types\User $tu
   * @param Chat $chat
   */
  private function processTextedMember(
    TelegramUser $tu,
    Chat $chat
  ) {
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