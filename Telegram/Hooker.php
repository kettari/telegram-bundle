<?php
/**
 * Created by PhpStorm.
 * User: ant
 * Date: 18.03.2017
 * Time: 18:01
 */

namespace Kaula\TelegramBundle\Telegram;


use Kaula\TelegramBundle\Entity\Chat;
use Kaula\TelegramBundle\Entity\Hook;
use Kaula\TelegramBundle\Entity\User;
use Kaula\TelegramBundle\Exception\HookException;
use Kaula\TelegramBundle\Telegram\Command\AbstractCommand;
use unreal4u\TelegramAPI\Telegram\Types\Update;

class Hooker
{

  /**
   * @var CommandBus
   */
  protected $bus;

  /**
   * Hooker constructor.
   *
   * @param CommandBus $bus
   */
  public function __construct(CommandBus $bus)
  {
    $this->bus = $bus;
  }

  /**
   * Creates hook.
   *
   * @param \unreal4u\TelegramAPI\Telegram\Types\Update $update
   * @param string $class_name
   * @param string $method_name
   * @param string $parameters
   */
  public function createHook(
    Update $update,
    $class_name,
    $method_name,
    $parameters = null
  ) {
    if (is_null($tm = $this->getMessage($update))) {
      throw new HookException('Unable to create hook: Message is NULL');
    }
    if (is_null($tu = $this->getUser($update))) {
      throw new HookException('Unable to create hook: Message->From is NULL');
    }

    $tc = $tm->chat;
    $d = $this->getBus()
      ->getBot()
      ->getContainer()
      ->get('doctrine');
    $em = $d->getManager();

    // Find chat object. If not found, create new
    $chat = $d->getRepository('KaulaTelegramBundle:Chat')
      ->findOneBy(['telegram_id' => $tc->id]);
    if (!$chat) {
      $chat = new Chat();
      $chat->setTelegramId($tc->id)
        ->setType($tc->type)
        ->setTitle($tc->title)
        ->setUsername($tc->username)
        ->setFirstName($tc->first_name)
        ->setLastName($tc->last_name)
        ->setAllMembersAreAdministrators($tc->all_members_are_administrators);
      $em->persist($chat);
    }
    // Find user object. If not found, create new
    $user = $d->getRepository('KaulaTelegramBundle:User')
      ->findOneBy(['telegram_id' => $tu->id]);
    if (!$user) {
      $user = new User();
      $user->setTelegramId($tu->id)
        ->setFirstName($tu->first_name)
        ->setLastName($tu->last_name)
        ->setUsername($tu->username);
      $em->persist($user);
    }

    // Finally, create hook with all things together
    $hook = new Hook();
    $hook->setCreated(new \DateTime())
      ->setChat($chat)
      ->setUser($user)
      ->setClassName($class_name)
      ->setMethodName($method_name)
      ->setParameters($parameters);
    $em->persist($hook);

    // Flush info to the database
    $em->flush();
  }

  /**
   * Tries to return correct Message object.
   *
   * @param \unreal4u\TelegramAPI\Telegram\Types\Update $update
   * @return \unreal4u\TelegramAPI\Telegram\Types\Message
   */
  private function getMessage(Update $update)
  {
    if (!is_null($update->message)) {
      return $update->message;
    } elseif (!is_null($update->callback_query) &&
      (!is_null($update->callback_query->message))
    ) {
      return $update->callback_query->message;
    } else {
      return null;
    }
  }

  /**
   * Tries to return correct User object.
   *
   * @param \unreal4u\TelegramAPI\Telegram\Types\Update $update
   * @return \unreal4u\TelegramAPI\Telegram\Types\User
   */
  private function getUser(Update $update)
  {
    if (!is_null($update->callback_query)) {
      return $update->callback_query->from;
    } elseif (!is_null($m = $this->getMessage($update))) {
      return $m->from;
    } else {
      return null;
    }
  }

  /**
   * Finds hook.
   *
   * @param \unreal4u\TelegramAPI\Telegram\Types\Update $update
   * @return null
   */
  public function findHook(Update $update)
  {
    // Try to find Message object
    if (is_null($tm = $this->getMessage($update))) {
      return null;
    }
    // Try to find User object
    if (is_null($tu = $this->getUser($update))) {
      return null;
    }

    $tc = $tm->chat;
    $d = $this->getBus()
      ->getBot()
      ->getContainer()
      ->get('doctrine');

    // Find chat object. If not found, nothing to do
    $chat = $d->getRepository('KaulaTelegramBundle:Chat')
      ->findOneBy(['telegram_id' => $tc->id]);
    if (!$chat) {
      return null;
    }

    // Find user object. If not found, nothing to do
    $user = $d->getRepository('KaulaTelegramBundle:User')
      ->findOneBy(['telegram_id' => $tu->id]);
    if (!$user) {
      return null;
    }

    // Find hook object
    $many_hooks = $d->getRepository('KaulaTelegramBundle:Hook')
      ->findBy(
        [
          'chat' => $chat->getId(),
          'user' => $user->getId(),
        ]
      );
    if (count($many_hooks) == 1) {
      return reset($many_hooks);
    } elseif (count($many_hooks) > 1) {
      $l = $this->getBus()
        ->getBot()
        ->getContainer()
        ->get('logger');
      $l->warning(
        'Multiple hooks found for user={user_id} and chat_id={chat_id}',
        ['user_id' => $tu->id, 'chat_id' => $tc->id]
      );

      // Try to delete all hooks
      $em = $d->getManager();
      /** @var Hook $one_hook */
      foreach ($many_hooks as $one_hook) {
        $em->remove($one_hook);
      }
      $em->flush();
    }

    return null;
  }

  /**
   * Executes the hook.
   *
   * @param \Kaula\TelegramBundle\Entity\Hook $hook
   * @param \unreal4u\TelegramAPI\Telegram\Types\Update $update
   * @return Hooker
   */
  public function executeHook(Hook $hook, Update $update)
  {
    if (class_exists($hook->getClassName())) {
      if (method_exists($hook->getClassName(), $hook->getMethodName())) {
        $command_name = $hook->getClassName();
        $method_name = $hook->getMethodName();

        /** @var AbstractCommand $command */
        $command = new $command_name($this->getBus(), $update);
        $command->$method_name($hook->getParameters());
      } else {
        throw new HookException(
          'Unable to execute the hook. Method not exists "'.
          $hook->getMethodName().'"" for the class: '.$hook->getClassName()
        );
      }
    } else {
      throw new HookException(
        'Unable to execute the hook. Class not exists: '.$hook->getClassName()
      );
    }

    return $this;
  }

  /**
   * Deletes the hook.
   *
   * @param \Kaula\TelegramBundle\Entity\Hook $hook
   */
  public function deleteHook(Hook $hook)
  {
    $d = $this->getBus()
      ->getBot()
      ->getContainer()
      ->get('doctrine');
    $em = $d->getManager();

    // Find hook object
    $em->remove($hook);
    $em->flush();
  }

  /**
   * @return CommandBus
   */
  public function getBus(): CommandBus
  {
    return $this->bus;
  }
}