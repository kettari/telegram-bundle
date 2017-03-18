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

class Hooker {

  /**
   * @var CommandBus
   */
  protected $bus;

  /**
   * Hooker constructor.
   *
   * @param CommandBus $bus
   */
  public function __construct(CommandBus $bus) {
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
  public function createHook(Update $update, $class_name, $method_name, $parameters = NULL) {
    if (is_null($update->message)) {
      throw new HookException('Unable to create hook: Message is NULL');
    }
    if (is_null($update->message->from)) {
      throw new HookException('Unable to create hook: Message->From is NULL');
    }

    $tc = $update->message->chat;
    $tu = $update->message->from;
    $d = $this->getBus()->getBot()
      ->getContainer()
      ->get('doctrine');
    $em = $d->getManager();

    // Find chat object. If not found, create new
    $chat = $d->getRepository('KaulaTelegramBundle:Chat')
      ->find($tc->id);
    if (!$chat) {
      $chat = new Chat();
      $chat->setId($tc->id)
        ->setChatType($tc->type)
        ->setTitle($tc->title)
        ->setUsername($tc->username)
        ->setFirstName($tc->first_name)
        ->setLastName($tc->last_name)
        ->setAllMembersAreAdministrators($tc->all_members_are_administrators);
      $em->persist($chat);
    }
    // Find user object. If not found, create new
    $user = $d->getRepository('KaulaTelegramBundle:User')
      ->find($tu->id);
    if (!$user) {
      $user = new User();
      $user->setId($tu->id)
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
   * Finds hook.
   *
   * @param \unreal4u\TelegramAPI\Telegram\Types\Update $update
   * @return null
   */
  public function findHook(Update $update) {
    if (is_null($update->message)) {
      return NULL;
    }
    if (is_null($update->message->from)) {
      return NULL;
    }

    $tc = $update->message->chat;
    $tu = $update->message->from;
    $d = $this->getBus()->getBot()
      ->getContainer()
      ->get('doctrine');

    // Find hook object
    $many_hooks = $d->getRepository('KaulaTelegramBundle:Hook')
      ->findBy(['chat' => $tc->id, 'user' => $tu->id]);
    if (count($many_hooks) == 1) {
      return reset($many_hooks);
    } elseif (count($many_hooks) > 1) {
      throw new HookException(sprintf('Multiple hooks found for chat_id=%s and user_id=%s',
        $tc->id, $tu->id));
    }

    return NULL;
  }

  /**
   * Executes the hook.
   *
   * @param \Kaula\TelegramBundle\Entity\Hook $hook
   * @param \unreal4u\TelegramAPI\Telegram\Types\Update $update
   * @return Hooker
   */
  public function executeHook(Hook $hook, Update $update) {
    if (class_exists($hook->getClassName())) {
      if (method_exists($hook->getClassName(), $hook->getMethodName())) {
        $command_name = $hook->getClassName();
        $method_name = $hook->getMethodName();

        /** @var AbstractCommand $command */
        $command = new $command_name($this->getBus(), $update);
        $command->$method_name($hook->getParameters());
      } else {
        throw new HookException('Unable to execute the hook. Method not exists "'.
          $hook->getMethodName().'"" for the class: '.$hook->getClassName());
      }
    } else {
      throw new HookException('Unable to execute the hook. Class not exists: '.
        $hook->getClassName());
    }

    return $this;
  }

  /**
   * Deletes the hook.
   *
   * @param \Kaula\TelegramBundle\Entity\Hook $hook
   */
  public function deleteHook(Hook $hook) {
    $d = $this->getBus()->getBot()
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
  public function getBus(): CommandBus {
    return $this->bus;
  }
}