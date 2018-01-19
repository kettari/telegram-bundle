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
use Kaula\TelegramBundle\Telegram\Event\JoinChatMemberBotEvent;
use Kaula\TelegramBundle\Telegram\Event\JoinChatMemberEvent;
use Kaula\TelegramBundle\Telegram\Event\JoinChatMembersManyEvent;
use Kaula\TelegramBundle\Telegram\Event\LeftChatMemberBotEvent;
use Kaula\TelegramBundle\Telegram\Event\LeftChatMemberEvent;
use Kaula\TelegramBundle\Telegram\Event\TextReceivedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use unreal4u\TelegramAPI\Telegram\Types\Chat as TelegramChat;
use unreal4u\TelegramAPI\Telegram\Types\Update;
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
      JoinChatMembersManyEvent::NAME => ['onMembersManyJoined', 90000],
      JoinChatMemberEvent::NAME      => ['onMemberJoined', 90000],
      LeftChatMemberEvent::NAME      => ['onMemberLeft', 90000],
      TextReceivedEvent::NAME        => ['onMemberTexted', 90000],
    ];
  }

  /**
   * When somebody joined the chat, updates database.
   *
   * @param JoinChatMembersManyEvent $event
   */
  public function onMembersManyJoined(JoinChatMembersManyEvent $event)
  {
    $dispatcher = $this->getBot()
      ->getEventDispatcher();
    $chat_entity = $this->prepareChat($event->getMessage()->chat);
    $users = $event->getMessage()->new_chat_members;

    /** @var TelegramUser $joined_user */
    foreach ($users as $joined_user) {
      $joined_member_event = new JoinChatMemberEvent(
        $event->getUpdate(), $joined_user, $chat_entity
      );
      $dispatcher->dispatch(JoinChatMemberEvent::NAME, $joined_member_event);
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
   * When somebody joined the chat, updates database.
   *
   * @param JoinChatMemberEvent $event
   */
  public function onMemberJoined(JoinChatMemberEvent $event)
  {
    $this->processJoinedMember(
      $event->getUpdate(),
      $event->getJoinedUser(),
      $event->getChatEntity()
    );

    // Flush changes
    $this->getDoctrine()
      ->getManager()
      ->flush();
  }

  /**
   * @param \unreal4u\TelegramAPI\Telegram\Types\Update $update
   * @param \unreal4u\TelegramAPI\Telegram\Types\User $tu
   * @param Chat $chatEntity
   */
  private function processJoinedMember(
    Update $update,
    TelegramUser $tu,
    Chat $chatEntity
  ) {
    $em = $this->getDoctrine()
      ->getManager();

    // Find user object. If not found, create new
    if (is_null(
      $userEntity = $this->getDoctrine()
        ->getRepository('KaulaTelegramBundle:User')
        ->findByTelegramId($tu->id)
    )) {
      $userEntity = $this->getBot()
        ->getUserHq()
        ->createAnonymousUser($tu);
    }

    // Find chat member object. If not found, create new
    $chatMember = $this->getDoctrine()
      ->getRepository(
        'KaulaTelegramBundle:ChatMember'
      )
      ->findOneBy(
        [
          'chat' => $chatEntity,
          'user' => $userEntity,
        ]
      );
    if (!$chatMember) {
      $chatMember = new ChatMember();
      $em->persist($chatMember);
    }
    $chatMember->setChat($chatEntity)
      ->setUser($userEntity)
      ->setJoinDate(new \DateTime('now', new \DateTimeZone('UTC')))
      ->setLeaveDate(null)
      ->setStatus('member');

    // Check if joined user is the bot itself and dispatch appropriate event if yes
    $config = $this->getBot()
      ->getContainer()
      ->getParameter('kettari_telegram');
    $selfUserId = $config['self_user_id'] ?? 0;
    if ($selfUserId == $tu->id) {
      $this->dispatchJoinedBotEvent($update, $tu, $chatEntity, $userEntity);
    }

    $this->getBot()
      ->getLogger()
      ->debug(
        'JOIN: self_user_id={self_user_id}, telegram_user_id={telegram_user_id}',
        ['self_user_id' => $selfUserId, 'telegram_user_id' => $tu->id]
      );
  }

  /**
   * @param Update $update
   * @param \unreal4u\TelegramAPI\Telegram\Types\User $joinedUser
   * @param \Kaula\TelegramBundle\Entity\Chat
   * @param \Kaula\TelegramBundle\Entity\User
   */
  private function dispatchJoinedBotEvent(
    $update,
    $joinedUser,
    $chatEntity,
    $userEntity
  ) {
    $botJoinedEvent = new JoinChatMemberBotEvent(
      $update, $joinedUser, $chatEntity, $userEntity
    );
    $this->getBot()
      ->getEventDispatcher()
      ->dispatch(JoinChatMemberBotEvent::NAME, $botJoinedEvent);
  }

  /**
   * When somebody left the chat, updates database.
   *
   * @param LeftChatMemberEvent $event
   */
  public function onMemberLeft(LeftChatMemberEvent $event)
  {
    $chat = $this->prepareChat($event->getMessage()->chat);
    $this->processLeftMember(
      $event->getUpdate(),
      $event->getMessage()->left_chat_member,
      $chat
    );

    // Flush changes
    $this->getDoctrine()
      ->getManager()
      ->flush();

    // Tell the Bot this request is handled
    $this->getBot()
      ->setRequestHandled(true);
  }

  /**
   * @param \unreal4u\TelegramAPI\Telegram\Types\Update $update
   * @param \unreal4u\TelegramAPI\Telegram\Types\User $tu
   * @param Chat $chatEntity
   */
  private function processLeftMember(
    Update $update,
    TelegramUser $tu,
    Chat $chatEntity
  ) {
    $em = $this->getDoctrine()
      ->getManager();

    // Find user object. If not found, create new
    $userEntity = $this->getDoctrine()
      ->getRepository('KaulaTelegramBundle:User')
      ->findByTelegramId($tu->id);
    if (!$userEntity) {
      $userEntity = $this->getBot()
        ->getUserHq()
        ->createAnonymousUser($tu);
    }

    // Find chat member object. If not found, create new
    $chatMember = $this->getDoctrine()
      ->getRepository(
        'KaulaTelegramBundle:ChatMember'
      )
      ->findOneBy(
        [
          'chat' => $chatEntity,
          'user' => $userEntity,
        ]
      );
    if (!$chatMember) {
      $chatMember = new ChatMember();
      $em->persist($chatMember);
    }
    $chatMember->setChat($chatEntity)
      ->setUser($userEntity)
      ->setLeaveDate(new \DateTime('now', new \DateTimeZone('UTC')))
      ->setStatus('left');

    // Check if left user is the bot itself and dispatch appropriate event if yes
    $config = $this->getBot()
      ->getContainer()
      ->getParameter('kettari_telegram');
    $selfUserId = $config['self_user_id'] ?? 0;
    if ($selfUserId == $tu->id) {
      $this->dispatchLeftBotEvent($update, $tu, $chatEntity, $userEntity);
    }

    $this->getBot()
      ->getLogger()
      ->debug(
        'LEFT: self_user_id={self_user_id}, telegram_user_id={telegram_user_id}',
        ['self_user_id' => $selfUserId, 'telegram_user_id' => $tu->id]
      );
  }

  /**
   * @param Update $update
   * @param \unreal4u\TelegramAPI\Telegram\Types\User $leftUser
   * @param \Kaula\TelegramBundle\Entity\Chat
   * @param \Kaula\TelegramBundle\Entity\User
   */
  private function dispatchLeftBotEvent(
    $update,
    $leftUser,
    $chatEntity,
    $userEntity
  ) {
    $botLeftEvent = new LeftChatMemberBotEvent(
      $update, $leftUser, $chatEntity, $userEntity
    );
    $this->getBot()
      ->getEventDispatcher()
      ->dispatch(LeftChatMemberBotEvent::NAME, $botLeftEvent);
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
   * Creates user that sent text to the chat.
   *
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
    $userEntity = $this->getDoctrine()
      ->getRepository('KaulaTelegramBundle:User')
      ->findByTelegramId($tu->id);
    if (!$userEntity) {
      $userEntity = $this->getBot()
        ->getUserHq()
        ->createAnonymousUser($tu);
    }

    // Find chat member object. If not found, create new
    $chatMember = $this->getDoctrine()
      ->getRepository(
        'KaulaTelegramBundle:ChatMember'
      )
      ->findOneBy(
        [
          'chat' => $chat,
          'user' => $userEntity,
        ]
      );
    if (!$chatMember) {
      $chatMember = new ChatMember();
      $em->persist($chatMember);
    }
    $chatMember->setChat($chat)
      ->setUser($userEntity)
      ->setStatus('member');
  }

}