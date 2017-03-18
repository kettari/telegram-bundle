<?php
/**
 * Created by PhpStorm.
 * User: ant
 * Date: 16.03.2017
 * Time: 18:11
 */

namespace Kaula\TelegramBundle\Telegram;


use Kaula\TelegramBundle\Entity\Chat;
use Kaula\TelegramBundle\Entity\Hook;
use Kaula\TelegramBundle\Entity\User;
use Kaula\TelegramBundle\Exception\HookException;
use Kaula\TelegramBundle\Exception\InvalidCommand;
use Kaula\TelegramBundle\Telegram\Command\AbstractCommand;
use Psr\Log\LoggerInterface;


use unreal4u\TelegramAPI\Telegram\Types\Update;

class CommandBus {

  /**
   * Bot this command bus belongs to.
   *
   * @var Bot
   */
  protected $bot;

  /**
   * Commands classes.
   *
   * @var array
   */
  protected $commands_classes = [];

  /**
   * CommandBus constructor.
   *
   * @param \Kaula\TelegramBundle\Telegram\Bot $bot
   */
  public function __construct(Bot $bot) {
    $this->bot = $bot;
  }

  /**
   * Registers command.
   *
   * @param string $command_class
   * @return \Kaula\TelegramBundle\Telegram\CommandBus
   */
  public function registerCommand($command_class) {
    if (class_exists($command_class)) {
      if (is_subclass_of($command_class, AbstractCommand::class)) {
        $this->commands_classes[$command_class] = TRUE;
      } else {
        throw new InvalidCommand('Unable to register command: '.$command_class.
          ' The command should be a subclass of '.AbstractCommand::class);
      }
    } else {
      throw new InvalidCommand('Unable to register command: '.$command_class.
        ' Class is not found');
    }

    return $this;
  }

  /**
   * Executes command that is registered with CommandBus.
   *
   * @param $command_name
   * @param \unreal4u\TelegramAPI\Telegram\Types\Update $update
   * @return bool Returns TRUE if appropriate command was found.
   */
  public function executeCommand($command_name, Update $update) {
    /** @var LoggerInterface $l */
    $l = $this->getBot()
      ->getContainer()
      ->get('logger');

    foreach ($this->commands_classes as $command_class => $placeholder) {
      /** @var AbstractCommand $command_class */
      if ($command_class::getName() == $command_name) {
        $l->debug('Executing /{command_name} command with the class "{class_name}"',
          ['command_name' => $command_name, 'class_name' => $command_class]);

        /** @var AbstractCommand $command */
        $command = new $command_class($this, $update);
        $command->execute();

        return TRUE;
      }
    }
    $l->debug('No class registered to handle /{command_name} command',
      ['command_name' => $command_name]);

    return FALSE;
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
      return;
    }
    if (is_null($update->message->from)) {
      return;
    }

    $tc = $update->message->chat;
    $tu = $update->message->from;
    $d = $this->getBot()
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
    $d = $this->getBot()
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
   * @return CommandBus
   */
  public function executeHook(Hook $hook, Update $update) {
    if (class_exists($hook->getClassName())) {
      if (method_exists($hook->getClassName(), $hook->getMethodName())) {
        $command_name = $hook->getClassName();
        $method_name = $hook->getMethodName();

        /** @var AbstractCommand $command */
        $command = new $command_name($this, $update);
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
    $d = $this->getBot()
      ->getContainer()
      ->get('doctrine');
    $em = $d->getManager();

    // Find hook object
    $em->remove($hook);
    $em->flush();
  }

  /**
   * Returns array of commands classes.
   *
   * @return array
   */
  public function getCommands() {
    return $this->commands_classes;
  }

  /**
   * Returns bot object.
   *
   * @return \Kaula\TelegramBundle\Telegram\Bot
   */
  public function getBot() {
    return $this->bot;
  }

}