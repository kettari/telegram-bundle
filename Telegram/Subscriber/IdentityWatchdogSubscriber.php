<?php
/**
 * Created by PhpStorm.
 * User: ant
 * Date: 25.04.2017
 * Time: 14:19
 */

namespace Kaula\TelegramBundle\Telegram\Subscriber;


use Kaula\TelegramBundle\Entity\Chat;
use Kaula\TelegramBundle\Entity\Role;
use Kaula\TelegramBundle\Entity\User;
use Kaula\TelegramBundle\Telegram\Event\MessageReceivedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use unreal4u\TelegramAPI\Telegram\Types\Chat as TelegramChat;
use unreal4u\TelegramAPI\Telegram\Types\User as TelegramUser;

class IdentityWatchdogSubscriber extends AbstractBotSubscriber implements EventSubscriberInterface
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
    return [MessageReceivedEvent::NAME => ['onMessageReceived', 90000]];
  }

  /**
   * Updates User and Chat for the current user; adds default roles if 1st
   * message ever.
   *
   * @param MessageReceivedEvent $event
   */
  public function onMessageReceived(MessageReceivedEvent $event)
  {
    // Update the user
    if (!is_null($event->getMessage()->from)) {
      // Get user and optionally mark it for update
      $user = $this->getUser($event->getMessage()->from);
      // Load anonymous roles and assign them to the user
      $roles = $this->getAnonymousRoles();
      $this->assignRoles($roles, $user);
    }

    // Update the chat
    $this->updateChat($event->getMessage()->chat);

    // Commit changes
    $this->getDoctrine()
      ->getManager()
      ->flush();
  }

  /**
   * Returns the User object by telegram user.
   *
   * @param TelegramUser $telegram_user
   * @return User|null
   */
  private function getUser($telegram_user)
  {
    // Find user object. If not found, create new
    $user = $this->getBot()
      ->getUserHq()
      ->getCurrentUser();
    if (!$user) {
      $user = new User();
      $this->getDoctrine()
        ->getManager()
        ->persist($user);
    }
    // Update information
    $user->setTelegramId($telegram_user->id)
      ->setFirstName($telegram_user->first_name)
      ->setLastName($telegram_user->last_name)
      ->setUsername($telegram_user->username);

    return $user;
  }

  /**
   * Returns array with roles for anonymous users.
   *
   * @return array
   */
  private function getAnonymousRoles()
  {
    $roles = $this->getDoctrine()
      ->getRepository('KaulaTelegramBundle:Role')
      ->findBy(['anonymous' => true]);
    if (0 == count($roles)) {
      throw new \LogicException('Roles for guests not found');
    }

    return $roles;
  }

  /**
   * Assigns specified roles to the user.
   *
   * @param array $roles
   * @param User $user
   */
  private function assignRoles(array $roles, User $user)
  {
    /** @var Role $single_role */
    foreach ($roles as $single_role) {
      if (!$user->getRoles()
        ->contains($single_role)
      ) {
        $user->addRole($single_role);
      }
    }
  }

  /**
   * Returns Chat object. Optionally it is added to persist if changes detected.
   *
   * @param TelegramChat $telegram_chat
   * @return Chat|null
   */
  private function updateChat($telegram_chat)
  {
    // Find chat object. If not found, create new
    $chat = $this->getDoctrine()
      ->getRepository('KaulaTelegramBundle:Chat')
      ->findOneBy(['telegram_id' => $telegram_chat->id]);
    if (!$chat) {
      $chat = new Chat();
      $this->getDoctrine()
        ->getManager()
        ->persist($chat);
    }
    // Update information
    $chat->setTelegramId($telegram_chat->id)
      ->setFirstName($telegram_chat->first_name)
      ->setLastName($telegram_chat->last_name)
      ->setUsername($telegram_chat->username)
      ->setType($telegram_chat->type)
      ->setTitle($telegram_chat->title)
      ->setAllMembersAreAdministrators(
        $telegram_chat->all_members_are_administrators
      );

    return $chat;
  }

}