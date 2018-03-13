<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram;

use Kettari\TelegramBundle\Entity\Hook;
use Kettari\TelegramBundle\Entity\Permission;
use Kettari\TelegramBundle\Entity\Role;
use Kettari\TelegramBundle\Exception\HookException;
use Kettari\TelegramBundle\Exception\InvalidCommandException;
use Kettari\TelegramBundle\Telegram\Command\AbstractCommand;
use Kettari\TelegramBundle\Telegram\Command\TelegramCommandInterface;
use Kettari\TelegramBundle\Telegram\Event\CommandExecutedEvent;
use Kettari\TelegramBundle\Telegram\Event\CommandUnauthorizedEvent;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use unreal4u\TelegramAPI\Telegram\Types\Update;
use unreal4u\TelegramAPI\Telegram\Types\User as TelegramUser;


class CommandBus implements CommandBusInterface
{
  /**
   * Commands classes.
   *
   * @var array
   */
  protected $commandsClasses = [];

  /**
   * @var LoggerInterface
   */
  private $logger;

  /**
   * @var RegistryInterface
   */
  private $doctrine;

  /**
   * @var EventDispatcherInterface
   */
  private $dispatcher;

  /**
   * @var UserHqInterface
   */
  private $userHq;

  /**
   * @var \Kettari\TelegramBundle\Telegram\CommunicatorInterface
   */
  private $communicator;

  /**
   * @var \Kettari\TelegramBundle\Telegram\PusherInterface
   */
  private $pusher;

  /**
   * CommandBus constructor.
   *
   * @param \Psr\Log\LoggerInterface $logger
   * @param \Symfony\Bridge\Doctrine\RegistryInterface $doctrine
   * @param EventDispatcherInterface $dispatcher
   * @param UserHqInterface $userHq
   * @param \Kettari\TelegramBundle\Telegram\CommunicatorInterface $communicator
   * @param \Kettari\TelegramBundle\Telegram\PusherInterface $pusher
   */
  public function __construct(
    LoggerInterface $logger,
    RegistryInterface $doctrine,
    EventDispatcherInterface $dispatcher,
    UserHqInterface $userHq,
    CommunicatorInterface $communicator,
    PusherInterface $pusher
  ) {
    $this->logger = $logger;
    $this->doctrine = $doctrine;
    $this->dispatcher = $dispatcher;
    $this->userHq = $userHq;
    $this->communicator = $communicator;
    $this->pusher = $pusher;
  }

  /**
   * {@inheritdoc}
   */
  public function registerCommand(string $commandClass): CommandBusInterface
  {
    $this->logger->debug(
      'Registering command class "{command_class}"',
      ['command_class' => $commandClass]
    );

    if (class_exists($commandClass)) {
      if (is_subclass_of($commandClass, TelegramCommandInterface::class)) {
        $this->commandsClasses[$commandClass] = true;
      } else {
        throw new InvalidCommandException(
          'Unable to register command: "'.$commandClass.
          '" The command should implement '.TelegramCommandInterface::class.
          ' interface'
        );
      }
    } else {
      throw new InvalidCommandException(
        'Unable to register command: '.$commandClass.' Class is not found'
      );
    }

    $this->logger->info(
      'Command class "{command_class}" registered',
      ['command_class' => $commandClass]
    );

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isCommandRegistered(string $commandName): bool
  {
    foreach ($this->commandsClasses as $commandClass => $placeholder) {
      /** @var AbstractCommand $commandClass */
      if ($commandClass::getName() == $commandName) {
        return true;
      }
    }

    return false;
  }

  /**
   * {@inheritdoc}
   */
  public function executeCommand(
    Update $update,
    string $commandName,
    string $parameter = ''
  ): bool {
    $this->logger->debug(
      'About to execute command "{command_name}" with parameter "{parameter}" for the update ID={update_id}',
      [
        'command_name' => $commandName,
        'parameter'    => $parameter,
        'update_id'    => $update->update_id,
      ]
    );

    foreach ($this->commandsClasses as $commandClass => $placeholder) {
      /** @var AbstractCommand $commandClass */
      if ($commandClass::getName() == $commandName) {

        // Check permissions for current user
        if (!$this->isAuthorized($update->message->from, $commandClass)) {
          $this->logger->notice(
            'User is not authorized to execute /{command_name} command with the class "{class_name}"',
            ['command_name' => $commandName, 'class_name' => $commandClass]
          );

          // Dispatch command is unauthorized
          $this->dispatchUnauthorizedCommand($update, $commandName, $parameter);

          return false;
        }

        // User is authorized, OK
        $this->logger->info(
          'Executing /{command_name} command with the parameter "{parameter}"',
          [
            'command_name' => $commandName,
            'class_name'   => $commandClass,
            'parameter'    => $parameter,
            'update_id'    => $update->update_id,
          ]
        );

        /** @var AbstractCommand $command */
        $command = new $commandClass($this, $update);
        $command->initialize($parameter)
          ->execute();

        $this->logger->debug(
          'Command /{command_name} executed with the class "{class_name}"',
          ['command_name' => $commandName, 'class_name' => $commandClass]
        );

        // Dispatch command is executed
        $this->dispatchCommandExecuted($update, $command);

        return true;
      }
    }

    return false;
  }

  /**
   * Return true if telegram user is authorized to execute specified command.
   *
   * @param \unreal4u\TelegramAPI\Telegram\Types\User $telegramUser
   * @param \Kettari\TelegramBundle\Telegram\Command\AbstractCommand $command
   * @return bool
   */
  public function isAuthorized(TelegramUser $telegramUser, $command): bool
  {
    $this->logger->debug(
      'Checking Telegram user ID={user_id} authorization to execute command "{command_name}"',
      ['user_id' => $telegramUser->id, 'command_name' => $command::getName()]
    );

    // Find user object
    $user = $this->doctrine->getRepository('KettariTelegramBundle:User')
      ->findOneByTelegramId($telegramUser->id);
    if (is_null($user)) {
      $this->logger->debug(
        'Telegram user ID={user_id} not found in the database',
        ['user_id' => $telegramUser->id]
      );

      return false;
    }
    // Fetch roles and iterate permissions
    $roles = $user->getRoles();
    $userPermissions = [];
    /** @var Role $roleItem */
    foreach ($roles as $roleItem) {
      $permissionsCollection = $roleItem->getPermissions();
      $permissionsArray = [];
      /** @var Permission $permissionItem */
      foreach ($permissionsCollection as $permissionItem) {
        $permissionsArray[] = $permissionItem->getName();
      }
      $userPermissions = array_merge($userPermissions, $permissionsArray);
    }

    $requiredPermissions = $command::getRequiredPermissions();

    // First check required permissions against existing permissions
    // and then check all required permissions are present
    $applicablePermissions = array_intersect(
      $requiredPermissions,
      $userPermissions
    );
    $result = !array_diff($applicablePermissions, $requiredPermissions) &&
      !array_diff($requiredPermissions, $applicablePermissions);

    // Write to the log permissions check result
    $this->logger->info(
      'Command authorization check result: {auth_check}',
      [
        'auth_check'             => $result ? 'OK' : 'not authorized',
        'required_permissions'   => $requiredPermissions,
        'user_permissions'       => $userPermissions,
        'applicable_permissions' => $applicablePermissions,
      ]
    );

    return $result;
  }

  /**
   * Dispatches command is unauthorized.
   *
   * @param Update $update
   * @param string $commandName
   * @param string $parameter
   */
  private function dispatchUnauthorizedCommand(
    Update $update,
    string $commandName,
    string $parameter
  ) {
    // Dispatch command event
    $commandUnauthorizedEvent = new CommandUnauthorizedEvent(
      $update, $commandName, $parameter
    );
    $this->dispatcher->dispatch(
      CommandUnauthorizedEvent::NAME,
      $commandUnauthorizedEvent
    );
  }

  /**
   * Dispatches command is executed.
   *
   * @param Update $update
   * @param \Kettari\TelegramBundle\Telegram\Command\AbstractCommand $command
   */
  private function dispatchCommandExecuted(
    Update $update,
    AbstractCommand $command
  ) {
    // Dispatch command event
    $commandExecutedEvent = new CommandExecutedEvent($update, $command);
    $this->dispatcher->dispatch(
      CommandExecutedEvent::NAME,
      $commandExecutedEvent
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getCommands(): array
  {
    return $this->commandsClasses;
  }

  /**
   * @inheritDoc
   */
  public function getDoctrine(): RegistryInterface
  {
    return $this->doctrine;
  }

  /**
   * {@inheritdoc}
   */
  public function getDispatcher(): EventDispatcherInterface
  {
    return $this->dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public function getUserHq(): UserHqInterface
  {
    return $this->userHq;
  }

  /**
   * @inheritDoc
   */
  public function getCommunicator(): CommunicatorInterface
  {
    return $this->communicator;
  }

  /**
   * @inheritDoc
   */
  public function getPusher(): PusherInterface
  {
    return $this->pusher;
  }

  /**
   * {@inheritdoc}
   */
  public function createHook(
    Update $update,
    string $className,
    string $methodName,
    string $parameters = ''
  ) {
    $this->logger->debug(
      'Creating hook for the update ID={update_id} with class name "{class_name}"::"{method_name}"',
      [
        'update_id'   => $update->update_id,
        'class_name'  => $className,
        'method_name' => $methodName,
      ]
    );

    if (is_null($telegramMessage = $this->getMessageFromUpdate($update))) {
      throw new HookException('Unable to create hook: Message is NULL');
    }
    if (is_null($telegramUser = $this->getUserFromUpdate($update))) {
      throw new HookException('Unable to create hook: Message->From is NULL');
    }

    // Find chat object. If not found, create new
    /** @var \Kettari\TelegramBundle\Entity\Chat $chat */
    $chat = $this->doctrine->getRepository('KettariTelegramBundle:Chat')
      ->findOneByTelegramId($telegramMessage->chat->id);
    if (!$chat) {
      throw new \LogicException(
        'Chat entity expected to exist and not found while creating the hook.'
      );
    }
    // Find user object. If not found, create new
    /** @var \Kettari\TelegramBundle\Entity\User $user */
    $user = $this->doctrine->getRepository('KettariTelegramBundle:User')
      ->findOneByTelegramId($telegramUser->id);
    if (!$user) {
      throw new \LogicException(
        'User entity expected to exist and not found while creating the hook.'
      );
    }

    // Finally, create hook with all things together
    $hook = new Hook();
    $hook->setCreated(new \DateTime('now', new \DateTimeZone('UTC')))
      ->setChat($chat)
      ->setUser($user)
      ->setClassName($className)
      ->setMethodName($methodName)
      ->setParameters($parameters);
    $this->doctrine->getManager()
      ->persist($hook);
    $this->doctrine->getManager()
      ->flush();

    $this->logger->info(
      'Hook ID={hook_id} created for the update ID={update_id} with class name "{class_name}"::"{method_name}"',
      [
        'hook_id'     => $hook->getId(),
        'update_id'   => $update->update_id,
        'class_name'  => $className,
        'method_name' => $methodName,
        'chat_id'     => $chat->getId(),
        'user_id'     => $user->getId(),
      ]
    );
  }

  /**
   * Tries to return correct Message object.
   *
   * @param \unreal4u\TelegramAPI\Telegram\Types\Update $update
   * @return \unreal4u\TelegramAPI\Telegram\Types\Message
   */
  private function getMessageFromUpdate(Update $update)
  {
    if (!is_null($update->message)) {
      return $update->message;
    } elseif (!is_null($update->callback_query) &&
      (!is_null($update->callback_query->message))) {
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
  private function getUserFromUpdate(Update $update)
  {
    if (!is_null($update->callback_query)) {
      return $update->callback_query->from;
    } elseif (!is_null($m = $this->getMessageFromUpdate($update))) {
      return $m->from;
    } else {
      return null;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function findHook(Update $update)
  {
    $this->logger->debug(
      'Searching hook for the update ID={update_id}',
      ['update_id' => $update->update_id]
    );

    // Try to find Message object
    if (is_null($telegramMessage = $this->getMessageFromUpdate($update))) {
      $this->logger->debug('No message within the update');

      return null;
    }
    // Try to find User object
    if (is_null($telegramUser = $this->getUserFromUpdate($update))) {
      $this->logger->debug('No user within the update');

      return null;
    }

    // Find chat object. If not found, nothing to do
    $chat = $this->doctrine->getRepository('KettariTelegramBundle:Chat')
      ->findOneByTelegramId($telegramMessage->chat->id);
    if (!$chat) {
      $this->logger->debug(
        'Chat entity not found for the chat ID={chat_id}',
        ['chat_id' => $telegramMessage->chat->id]
      );

      return null;
    }

    // Find user object. If not found, nothing to do
    $user = $this->doctrine->getRepository('KettariTelegramBundle:User')
      ->findOneByTelegramId($telegramUser->id);
    if (!$user) {
      $this->logger->debug(
        'User entity not found for the user ID={user_id}',
        ['user_id' => $telegramUser->id]
      );

      return null;
    }

    // Find hook object
    $activeHooks = $this->doctrine->getRepository('KettariTelegramBundle:Hook')
      ->findActive($chat->getId(), $user->getId());
    if (count($activeHooks) == 1) {
      /** @var Hook $hook */
      $hook = reset($activeHooks);
      $this->logger->info(
        'Found hook ID={hook_id} for the update ID={update_id}: chat entity ID={chat_id}, user entity ID={user_id}',
        [
          'hook_id'   => $hook->getId(),
          'update_id' => $update->update_id,
          'chat_id'   => $chat->getId(),
          'user_id'   => $user->getId(),
        ]
      );

      // One hook is OK, return it
      return $hook;
    } elseif (count($activeHooks) > 1) {
      $this->logger->warning(
        'Multiple hooks found for user ID={user_id} and chat ID={chat_id}',
        [
          'user_id' => $telegramUser->id,
          'chat_id' => $telegramMessage->chat->id,
        ]
      );

      // Try to delete all hooks
      /** @var Hook $oneHook */
      foreach ($activeHooks as $oneHook) {
        $this->doctrine->getManager()
          ->remove($oneHook);
      }
      $this->doctrine->getManager()
        ->flush();
    }

    $this->logger->info(
      'No hooks found for the update ID={update_id}: chat entity ID={chat_id}, user entity ID={user_id}',
      [
        'update_id' => $update->update_id,
        'chat_id'   => $chat->getId(),
        'user_id'   => $user->getId(),
      ]
    );

    return null;
  }

  /**
   * {@inheritdoc}
   */
  public function executeHook(Hook $hook, Update $update): CommandBusInterface
  {
    $this->logger->debug(
      'Executing hook ID={hook_id} for the update ID={update_id}',
      ['hook_id' => $hook->getId(), 'update_id' => $update->update_id]
    );

    if (class_exists($hook->getClassName())) {
      if (method_exists($hook->getClassName(), $hook->getMethodName())) {
        $commandName = $hook->getClassName();
        $methodName = $hook->getMethodName();

        $this->logger->debug(
          'Hook command name "{command_name}", method name "{method_name}"',
          ['command_name' => $commandName, 'method_name' => $methodName]
        );

        /** @var AbstractCommand $command */
        $command = new $commandName($this, $update);
        $command->$methodName($hook->getParameters());
      } else {
        throw new HookException(
          'Unable to execute the hook ID='.$hook->getId().
          '. Method not exists "'.$hook->getMethodName().'"" for the class: '.
          $hook->getClassName()
        );
      }
    } else {
      throw new HookException(
        'Unable to execute the hook ID='.$hook->getId().'. Class not exists: '.
        $hook->getClassName()
      );
    }

    $this->logger->info(
      'Hook ID={hook_id} for the update ID={update_id} executed',
      ['hook_id' => $hook->getId(), 'update_id' => $update->update_id]
    );

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteHook(Hook $hook)
  {
    $this->logger->info(
      'Hook ID={hook_id} deleted',
      ['hook_id' => $hook->getId()]
    );

    $this->doctrine->getManager()
      ->remove($hook);
    $this->doctrine->getManager()
      ->flush();
  }
}