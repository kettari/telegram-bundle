<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram;

use Kettari\TelegramBundle\Entity\Hook;
use Kettari\TelegramBundle\Entity\Permission;
use Kettari\TelegramBundle\Entity\Role;
use Kettari\TelegramBundle\Exception\CommandNotFoundException;
use Kettari\TelegramBundle\Exception\HookException;
use Kettari\TelegramBundle\Telegram\Command\TelegramCommandInterface;
use Kettari\TelegramBundle\Telegram\Event\CommandExecutedEvent;
use Kettari\TelegramBundle\Telegram\Event\CommandUnauthorizedEvent;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use unreal4u\TelegramAPI\Telegram\Types\Update;
use unreal4u\TelegramAPI\Telegram\Types\User as TelegramUser;


class CommandBus implements CommandBusInterface
{
  use TelegramObjectsRetrieverTrait;

  /**
   * @var \Symfony\Component\DependencyInjection\Container
   */
  private $container;

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
   * Commands classes.
   *
   * @var array
   */
  private $commandServices = [];

  /**
   * CommandBus constructor.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * @param \Psr\Log\LoggerInterface $logger
   * @param \Symfony\Bridge\Doctrine\RegistryInterface $doctrine
   * @param EventDispatcherInterface $dispatcher
   */
  public function __construct(
    ContainerInterface $container,
    LoggerInterface $logger,
    RegistryInterface $doctrine,
    EventDispatcherInterface $dispatcher
  ) {
    $this->container = $container;
    $this->logger = $logger;
    $this->doctrine = $doctrine;
    $this->dispatcher = $dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public function registerCommand(
    string $commandName,
    string $serviceId
  ): CommandBusInterface {
    $this->commandServices[$commandName] = $serviceId;
    $this->logger->info(
      'Command "{command_name}" as "{service_id}" service registered',
      ['command_name' => $commandName, 'service_id' => $serviceId]
    );

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function executeCommand(
    Update $update,
    string $commandName,
    string $commandParameter = ''
  ): bool {
    $this->logger->debug(
      'About to execute command "{command_name}" with parameter "{parameter}"',
      [
        'command_name' => $commandName,
        'parameter'    => empty($commandParameter) ? '(empty)' : $commandParameter,
        'update_id'    => $update->update_id,
      ]
    );

    if (!$this->isCommandRegistered($commandName)) {
      throw new CommandNotFoundException(
        sprintf('Command "%s" is not registered.', $commandName)
      );
    }

    /** @var \Kettari\TelegramBundle\Telegram\Command\TelegramCommandInterface $commandService */
    $commandService = $this->container->get(
      $this->commandServices[$commandName]
    );
    // Check permissions for current user
    if (!$this->isAuthorized(
      $this->getUserFromUpdate($update),
      $commandService
    )) {
      $this->logger->notice(
        'User is not authorized to execute /{command_name} command with the class "{class_name}"',
        ['command_name' => $commandName, 'class_name' => $commandName]
      );

      // Dispatch command is unauthorized
      $this->dispatchUnauthorizedCommand(
        $update,
        $commandName,
        $commandParameter
      );

      return false;
    }

    // User is authorized, OK
    $this->logger->info(
      'Executing /{command_name} command with the parameter "{parameter}"',
      [
        'command_name' => $commandName,
        'service_id'   => $this->commandServices[$commandName],
        'class_name'   => get_class($commandService),
        'parameter'    => empty($commandParameter) ? '(empty)' : $commandParameter,
        'update_id'    => $update->update_id,
      ]
    );

    // Execute command
    $commandService->execute($update, $commandParameter);

    $this->logger->debug(
      'Command /{command_name} executed with the class "{class_name}"',
      [
        'command_name' => $commandName,
        'class_name'   => get_class($commandService),
      ]
    );

    // Dispatch command is executed
    $this->dispatchCommandExecuted($update, $commandService);

    return true;
  }

  /**
   * {@inheritdoc}
   */
  public function isCommandRegistered(string $commandName): bool
  {
    return isset($this->commandServices[$commandName]);
  }

  /**
   * Return true if telegram user is authorized to execute specified command.
   *
   * @param \unreal4u\TelegramAPI\Telegram\Types\User $telegramUser
   * @param \Kettari\TelegramBundle\Telegram\Command\TelegramCommandInterface $command
   * @return bool
   */
  public function isAuthorized(
    TelegramUser $telegramUser,
    TelegramCommandInterface $command
  ): bool {
    $this->logger->debug(
      'Checking Telegram user ID={user_id} authorization to execute command "{command_name}"',
      [
        'user_id'      => $telegramUser->id,
        'command_name' => $command::getName(),
      ]
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
   * @param \Kettari\TelegramBundle\Telegram\Command\TelegramCommandInterface $command
   */
  private function dispatchCommandExecuted(
    Update $update,
    TelegramCommandInterface $command
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
    $classes = [];
    foreach ($this->commandServices as $commandName => $serviceId) {
      $classes[] = $this->container->get($serviceId);
    }

    return $classes;
  }

  /**
   * {@inheritdoc}
   */
  public function createHook(
    Update $update,
    string $serviceId,
    string $methodName = 'handler',
    string $parameter = ''
  ) {
    $this->logger->debug(
      'Creating hook with service ID= "{service_id}"::"{method_name}"',
      [
        'update_id'   => $update->update_id,
        'service_id'  => $serviceId,
        'method_name' => $methodName,
      ]
    );

    if (is_null($telegramMessage = $this->getMessageFromUpdate($update))) {
      throw new HookException('Unable to create hook: message is null');
    }
    if (is_null($telegramUser = $this->getUserFromUpdate($update))) {
      throw new HookException('Unable to create hook: user is null');
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
      ->setServiceId($serviceId)
      ->setMethodName($methodName)
      ->setParameter($parameter);
    $this->doctrine->getManager()
      ->persist($hook);
    $this->doctrine->getManager()
      ->flush();

    $this->logger->info(
      'Hook ID={hook_id} created with service ID="{service_id}"::"{method_name}"',
      [
        'hook_id'     => $hook->getId(),
        'update_id'   => $update->update_id,
        'service_id'  => $serviceId,
        'method_name' => $methodName,
        'chat_id'     => $chat->getId(),
        'user_id'     => $user->getId(),
      ]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function findHook(Update $update)
  {
    $this->logger->debug(
      'Searching hook',
      ['update_id' => $update->update_id]
    );

    // Try to find Message object
    if (is_null($telegramMessage = $this->getMessageFromUpdate($update))) {
      $this->logger->debug('No message within the update');

      return null;
    }
    // Try to find User object
    if (UpdateTypeResolver::UT_CALLBACK_QUERY ==
      UpdateTypeResolver::getUpdateType($update)) {
      $telegramUser = $update->callback_query->from;
    } else {
      $telegramUser = $this->getUserFromUpdate($update);
    }
    if (is_null($telegramUser)) {
      $this->logger->debug('No user within the update');

      return null;
    }
    // Find chat object. If not found, nothing to do
    $chat = $this->doctrine->getRepository('KettariTelegramBundle:Chat')
      ->findOneByTelegramId($telegramMessage->chat->id);
    if (!$chat) {
      throw new HookException(
        'Chat entity not found for Telegram chat ID='.$telegramMessage->chat->id
      );
    }
    // Find user object. If not found, nothing to do
    $user = $this->doctrine->getRepository('KettariTelegramBundle:User')
      ->findOneByTelegramId($telegramUser->id);
    if (!$user) {
      throw new HookException(
        'User entity not found for Telegram user ID='.$telegramUser->id
      );
    }

    // Find hook object
    $activeHooks = $this->doctrine->getRepository('KettariTelegramBundle:Hook')
      ->findActive($chat->getId(), $user->getId());
    if (count($activeHooks) == 1) {
      /** @var Hook $hook */
      $hook = reset($activeHooks);
      $this->logger->info(
        'Found hook ID={hook_id}: chat entity ID={chat_id}, user entity ID={user_id}',
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
      'No hooks found: chat entity ID={chat_id}, user entity ID={user_id}',
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
      'Executing hook ID={hook_id}',
      [
        'hook_id'    => $hook->getId(),
        'update_id'  => $update->update_id,
        'service_id' => $hook->getServiceId(),
      ]
    );

    /** @var \Kettari\TelegramBundle\Telegram\Command\TelegramCommandInterface $commandService */
    $hookService = $this->container->get($hook->getServiceId());

    if (method_exists($hookService, $hook->getMethodName())) {
      $methodName = $hook->getMethodName();

      $this->logger->debug(
        'Hook service ID "{service_id}" for "{class_name}", method name "{method_name}"',
        [
          'service_id'  => $hook->getServiceId(),
          'class_name'  => get_class($hookService),
          'method_name' => $methodName,
        ]
      );

      // Execute the hook
      $hookService->$methodName($update, $hook->getParameter());

    } else {
      throw new HookException(
        'Unable to execute the hook ID='.$hook->getId().'. Method not exists "'.
        $hook->getMethodName().'"" for the class: '.get_class($hookService)
      );
    }

    $this->logger->info(
      'Hook ID={hook_id} executed',
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