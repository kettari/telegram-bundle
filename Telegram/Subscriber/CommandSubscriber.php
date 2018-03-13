<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram\Subscriber;


use Kettari\TelegramBundle\Telegram\Communicator;
use Kettari\TelegramBundle\Telegram\Event\CommandReceivedEvent;
use Kettari\TelegramBundle\Telegram\Event\CommandUnauthorizedEvent;
use Kettari\TelegramBundle\Telegram\Event\CommandUnknownEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use unreal4u\TelegramAPI\Telegram\Types\ReplyKeyboardRemove;
use unreal4u\TelegramAPI\Telegram\Types\Update;

class CommandSubscriber extends AbstractBotSubscriber implements EventSubscriberInterface
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
        $event->getCommandName(),
        $event->getParameter()
      );

      return;
    }

    // Execute the command
    $this->bus->executeCommand(
      $event->getUpdate(),
      $event->getCommandName(),
      $event->getParameter()
    );
    /*if ($this->bus->executeCommand(
      $event->getUpdate(),
      $event->getCommandName(),
      $event->getParameter()
    )) {
      // Set flag that request is handled
      $this->getBot()
        ->setRequestHandled(true);
    }*/
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
   * @param string $parameter
   */
  private function dispatchUnknownCommandReceived(
    Update $update,
    $command_name,
    $parameter
  ) {
    // Dispatch command event
    $command_unknown_event = new CommandUnknownEvent(
      $update, $command_name, $parameter
    );
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
      $this->bus->getTrans()
        ->trans('command.unknown'),
      Communicator::PARSE_MODE_PLAIN,
      new ReplyKeyboardRemove()
    );

    // Set flag that request is handled
    /*$this->getBot()
      ->setRequestHandled(true);*/
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
      $this->bus->getTrans()
        ->trans('command.forbidden'),
      Communicator::PARSE_MODE_PLAIN
    );

    // Set flag that request is handled
    /*$this->getBot()
      ->setRequestHandled(true);*/
    $this->logger->info(
      'CommandSubscriber::CommandUnauthorizedEvent for the message ID={message_id}, command "{command_name}" processed',
      [
        'message_id'   => $event->getMessage()->message_id,
        'command_name' => $event->getCommandName(),
      ]
    );
  }
}