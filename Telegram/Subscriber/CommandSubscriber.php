<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram\Subscriber;


use Kettari\TelegramBundle\Telegram\CommandBusInterface;
use Kettari\TelegramBundle\Telegram\Communicator;
use Kettari\TelegramBundle\Telegram\CommunicatorInterface;
use Kettari\TelegramBundle\Telegram\Event\CommandReceivedEvent;
use Kettari\TelegramBundle\Telegram\Event\CommandUnauthorizedEvent;
use Kettari\TelegramBundle\Telegram\Event\CommandUnknownEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Translation\TranslatorInterface;
use unreal4u\TelegramAPI\Telegram\Types\ReplyKeyboardRemove;
use unreal4u\TelegramAPI\Telegram\Types\Update;

class CommandSubscriber extends AbstractBotSubscriber implements EventSubscriberInterface
{
  /**
   * @var EventDispatcherInterface
   */
  private $dispatcher;

  /**
   * @var \Kettari\TelegramBundle\Telegram\CommandBusInterface
   */
  private $bus;

  /**
   * @var CommunicatorInterface
   */
  private $communicator;

  /**
   * @var \Symfony\Component\Translation\TranslatorInterface
   */
  private $trans;

  /**
   * CommandSubscriber constructor.
   *
   * @param \Psr\Log\LoggerInterface $logger
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher
   * @param \Kettari\TelegramBundle\Telegram\CommandBusInterface $bus
   * @param \Kettari\TelegramBundle\Telegram\CommunicatorInterface $communicator
   * @param \Symfony\Component\Translation\TranslatorInterface $translator
   */
  public function __construct(
    LoggerInterface $logger,
    EventDispatcherInterface $dispatcher,
    CommandBusInterface $bus,
    CommunicatorInterface $communicator,
    TranslatorInterface $translator
  ) {
    parent::__construct($logger);
    $this->dispatcher = $dispatcher;
    $this->bus = $bus;
    $this->communicator = $communicator;
    $this->trans = $translator;
  }

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
      CommandReceivedEvent::NAME     => 'onCommandReceived',
      CommandUnknownEvent::NAME      => 'onCommandUnknown',
      CommandUnauthorizedEvent::NAME => 'onCommandUnauthorized',
    ];
  }

  /**
   * Executes command.
   *
   * @param CommandReceivedEvent $event
   */
  public function onCommandReceived(CommandReceivedEvent $event)
  {
    $this->logger->debug(
      'Processing CommandSubscriber::CommandReceivedEvent for the message ID={message_id}, command "{command_name}"',
      [
        'message_id'   => $event->getMessage()->message_id,
        'command_name' => $event->getCommandName(),
      ]
    );

    if (!$this->bus->isCommandRegistered($event->getCommandName())) {
      $this->logger->notice(
        'No class registered to handle /{command_name} command',
        ['command_name' => $event->getCommandName()]
      );

      // Unknown command
      $this->dispatchUnknownCommandReceived(
        $event->getUpdate(),
        $event->getCommandName()
      );

      return;
    }

    // Remove all current hooks prior to execute command
    $this->bus->deleteAllHooks($event->getUpdate());

    // Execute the command
    $this->bus->executeCommand(
      $event->getUpdate(),
      $event->getCommandName(),
      $event->getParameter()
    );

    $this->logger->info(
      'CommandSubscriber::CommandReceivedEvent for the message ID={message_id}, command "{command_name}" processed',
      [
        'message_id'   => $event->getMessage()->message_id,
        'command_name' => $event->getCommandName(),
      ]
    );
  }

  /**
   * Dispatches command is unknown.
   *
   * @param Update $update
   * @param string $command_name
   */
  private function dispatchUnknownCommandReceived(
    Update $update,
    $command_name
  ) {
    // Dispatch command event
    $command_unknown_event = new CommandUnknownEvent($update, $command_name);
    $this->dispatcher->dispatch(
      CommandUnknownEvent::NAME,
      $command_unknown_event
    );
  }

  /**
   * Unknown command.
   *
   * @param CommandUnknownEvent $event
   */
  public function onCommandUnknown(CommandUnknownEvent $event)
  {
    $this->logger->debug(
      'Processing CommandSubscriber::CommandUnknownEvent for the message ID={message_id}, command "{command_name}"',
      [
        'message_id'   => $event->getMessage()->message_id,
        'command_name' => $event->getCommandName(),
      ]
    );

    // Tell user command is not found
    $this->communicator->sendMessage(
      $event->getMessage()->chat->id,
      $this->trans->trans('command.unknown'),
      Communicator::PARSE_MODE_PLAIN,
      new ReplyKeyboardRemove()
    );

    $this->logger->info(
      'CommandSubscriber::CommandUnknownEvent for the message ID={message_id}, command "{command_name}" processed',
      [
        'message_id'   => $event->getMessage()->message_id,
        'command_name' => $event->getCommandName(),
      ]
    );
  }

  /**
   * User has insufficient permissions.
   *
   * @param CommandUnauthorizedEvent $event
   */
  public function onCommandUnauthorized(CommandUnauthorizedEvent $event)
  {
    $this->logger->debug(
      'Processing CommandSubscriber::CommandUnauthorizedEvent for the message ID={message_id}, command "{command_name}"',
      [
        'message_id'   => $event->getMessage()->message_id,
        'command_name' => $event->getCommandName(),
      ]
    );

    // Tell the user he is not authorized to execute the command
    $this->communicator->sendMessage(
      $event->getMessage()->chat->id,
      $this->trans->trans('command.forbidden'),
      Communicator::PARSE_MODE_PLAIN
    );

    // Set flag that request is handled
    $event->getKeeper()
      ->setRequestHandled(true);
    $this->logger->info(
      'CommandSubscriber::CommandUnauthorizedEvent for the message ID={message_id}, command "{command_name}" processed',
      [
        'message_id'   => $event->getMessage()->message_id,
        'command_name' => $event->getCommandName(),
      ]
    );
  }
}