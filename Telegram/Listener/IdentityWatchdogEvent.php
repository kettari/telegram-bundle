<?php
/**
 * Created by PhpStorm.
 * User: ant
 * Date: 22.03.2017
 * Time: 14:28
 */

namespace Kaula\TelegramBundle\Telegram\Listener;


use Doctrine\Bundle\DoctrineBundle\Registry;
use Kaula\TelegramBundle\Entity\Chat as ChatEntity;
use Kaula\TelegramBundle\Entity\Role;
use Kaula\TelegramBundle\Entity\User;
use Kaula\TelegramBundle\Telegram\Bot;
use unreal4u\TelegramAPI\Telegram\Types\Chat;
use unreal4u\TelegramAPI\Telegram\Types\Update;


/**
 * Class IdentityWatchdogEvent
 *
 * Updates User and Role information on every message.
 *
 * @package Kaula\TelegramBundle\Telegram\Listener
 */
class IdentityWatchdogEvent implements EventListenerInterface {

  public function execute(Bot $bot, Update $update) {
    $tc = $update->message->chat;
    $d = $bot->getContainer()
      ->get('doctrine');

    // Update user
    if (!is_null($update->message->from)) {
      $tu = $update->message->from;

      $user = $this->getUser($d, $tu);
      $roles = $this->getAnonymousRoles($d);
      $this->assignRoles($d, $roles, $user);
    }

    // Update chat
    $this->getChat($d, $tc);

    // Commit changes
    $d->getManager()
      ->flush();
  }

  /**
   * Returns User object. Optionally it is added to persist if changes detected.
   *
   * @param \Doctrine\Bundle\DoctrineBundle\Registry $d
   * @param \unreal4u\TelegramAPI\Telegram\Types\User $tu
   * @return \Kaula\TelegramBundle\Entity\User|null|object
   */
  private function getUser(Registry $d, \unreal4u\TelegramAPI\Telegram\Types\User $tu) {
    $em = $d->getManager();

    // Find user object. If not found, create new
    $user = $d->getRepository('KaulaTelegramBundle:User')
      ->findOneBy(['telegram_id' => $tu->id]);
    if (!$user) {
      $user = new User();
    }
    // Detect telegram-related changes
    if (($user->getTelegramId() != $tu->id) ||
      ($user->getFirstName() != $tu->first_name) ||
      ($user->getLastName() != $tu->last_name) ||
      ($user->getUsername() != $tu->username)
    ) {
      // Update information
      $user->setTelegramId($tu->id)
        ->setFirstName($tu->first_name)
        ->setLastName($tu->last_name)
        ->setUsername($tu->username);
      $em->persist($user);
    }

    return $user;
  }

  /**
   * Returns array with roles for anonymous users.
   *
   * @param \Doctrine\Bundle\DoctrineBundle\Registry $d
   * @return array
   */
  private function getAnonymousRoles(Registry $d) {
    $roles = $d->getRepository('KaulaTelegramBundle:Role')
      ->findBy(['anonymous' => TRUE]);
    if (0 == count($roles)) {
      throw new \LogicException('Roles for guests not found');
    }

    return $roles;
  }

  /**
   * Assigns specified roles to the user.
   *
   * @param \Doctrine\Bundle\DoctrineBundle\Registry $d
   * @param array $roles
   * @param \Kaula\TelegramBundle\Entity\User $user
   */
  private function assignRoles(Registry $d, array $roles, User $user) {
    $em = $d->getManager();

    /** @var Role $single_role */
    foreach ($roles as $single_role) {
      if (!$user->getRoles()
        ->contains($single_role)
      ) {
        $user->addRole($single_role);
        $em->persist($user);
      }
    }
  }

  /**
   * Returns Chat object. Optionally it is added to persist if changes detected.
   *
   * @param \Doctrine\Bundle\DoctrineBundle\Registry $d
   * @param \unreal4u\TelegramAPI\Telegram\Types\Chat $tc
   * @return \Kaula\TelegramBundle\Entity\Chat|null|object
   */
  private function getChat(Registry $d, Chat $tc) {
    $em = $d->getManager();

    // Find chat object. If not found, create new
    $chat = $d->getRepository('KaulaTelegramBundle:Chat')
      ->findOneBy(['telegram_id' => $tc->id]);
    if (!$chat) {
      $chat = new ChatEntity();
    }
    // Detect telegram-related changes
    if (($chat->getTelegramId() != $tc->id) ||
      ($chat->getFirstName() != $tc->first_name) ||
      ($chat->getLastName() != $tc->last_name) ||
      ($chat->getUsername() != $tc->username) ||
      ($chat->getType() != $tc->type) || ($chat->getTitle() != $tc->title) ||
      ($chat->getAllMembersAreAdministrators() !=
        $tc->all_members_are_administrators)
    ) {
      // Update information
      $chat->setTelegramId($tc->id)
        ->setFirstName($tc->first_name)
        ->setLastName($tc->last_name)
        ->setUsername($tc->username)
        ->setType($tc->type)
        ->setTitle($tc->title)
        ->setAllMembersAreAdministrators($tc->all_members_are_administrators);
      $em->persist($chat);
    }

    return $chat;
  }


}