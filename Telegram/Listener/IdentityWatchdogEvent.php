<?php
/**
 * Created by PhpStorm.
 * User: ant
 * Date: 22.03.2017
 * Time: 14:28
 */

namespace Kaula\TelegramBundle\Telegram\Listener;


use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;
use Kaula\TelegramBundle\Entity\Chat as ChatEntity;
use Kaula\TelegramBundle\Entity\Role;
use Kaula\TelegramBundle\Entity\User;
use Kaula\TelegramBundle\Telegram\Bot;
use Kaula\TelegramBundle\Telegram\UserHq;
use unreal4u\TelegramAPI\Telegram\Types\Chat;
use unreal4u\TelegramAPI\Telegram\Types\Update;
use unreal4u\TelegramAPI\Telegram\Types\User as TelegramUser;


/**
 * Class IdentityWatchdogEvent
 *
 * Updates User and Role information on every message.
 *
 * @package Kaula\TelegramBundle\Telegram\Listener
 */
class IdentityWatchdogEvent implements EventListenerInterface
{

  public function execute(Bot $bot, Update $update)
  {
    $tc = $update->message->chat;
    $d = $bot->getContainer()
      ->get('doctrine');

    // Update user
    if (!is_null($update->message->from)) {
      $tu = $update->message->from;

      // Get user and optionally mark it for update
      $user = $this->getUser($bot->getUserHq(), $d->getManager(), $tu);
      // Load anonymous roles and assign them to the user
      $roles = $this->getAnonymousRoles($d);
      $this->assignRoles($roles, $user);
    }

    // Update chat
    $this->updateChat($d, $tc);

    // Commit changes
    $d->getManager()
      ->flush();
  }

  /**
   * Returns User object. Optionally it is added to persist if changes detected.
   *
   * @param \Kaula\TelegramBundle\Telegram\UserHq $hq
   * @param \Doctrine\ORM\EntityManager $em
   * @param \unreal4u\TelegramAPI\Telegram\Types\User $tu
   * @return \Kaula\TelegramBundle\Entity\User|null|object
   */
  private function getUser(
    UserHq $hq,
    EntityManager $em,
    TelegramUser $tu
  ) {
    // Find user object. If not found, create new
    $user = $hq->getCurrentUser();
    if (!$user) {
      $user = new User();
      $em->persist($user);
    }
    // Update information
    $user->setTelegramId($tu->id)
      ->setFirstName($tu->first_name)
      ->setLastName($tu->last_name)
      ->setUsername($tu->username);

    return $user;
  }

  /**
   * Returns array with roles for anonymous users.
   *
   * @param \Doctrine\Bundle\DoctrineBundle\Registry $d
   * @return array
   */
  private function getAnonymousRoles(Registry $d)
  {
    $roles = $d->getRepository('KaulaTelegramBundle:Role')
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
   * @param \Kaula\TelegramBundle\Entity\User $user
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
   * @param \Doctrine\Bundle\DoctrineBundle\Registry $d
   * @param \unreal4u\TelegramAPI\Telegram\Types\Chat $tc
   * @return \Kaula\TelegramBundle\Entity\Chat|null|object
   */
  private function updateChat(Registry $d, Chat $tc)
  {
    $em = $d->getManager();

    // Find chat object. If not found, create new
    $chat = $d->getRepository('KaulaTelegramBundle:Chat')
      ->findOneBy(['telegram_id' => $tc->id]);
    if (!$chat) {
      $chat = new ChatEntity();
      $em->persist($chat);
    }
    // Update information
    $chat->setTelegramId($tc->id)
      ->setFirstName($tc->first_name)
      ->setLastName($tc->last_name)
      ->setUsername($tc->username)
      ->setType($tc->type)
      ->setTitle($tc->title)
      ->setAllMembersAreAdministrators($tc->all_members_are_administrators);

    return $chat;
  }


}