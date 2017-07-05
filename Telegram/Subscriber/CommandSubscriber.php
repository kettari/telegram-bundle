<?php
/**
 * Created by PhpStorm.
 * User: ant
 * Date: 27.04.2017
 * Time: 21:52
 */

namespace Kaula\TelegramBundle\Telegram\Subscriber;



use Kaula\TelegramBundle\Telegram\Event\CommandExecutedEvent;
use Kaula\TelegramBundle\Telegram\Event\CommandReceivedEvent;
use Kaula\TelegramBundle\Telegram\Event\CommandUnauthorizedEvent;
use Kaula\TelegramBundle\Telegram\Event\CommandUnknownEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
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
      CommandReceivedEvent::NAME => 'onCommandReceived',
      CommandUnknownEvent::NAME => 'onCommandUnknown',
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
    $l = $this->getBot()
      ->getContainer()
      ->get('logger');

    if (!$this->getBot()
      ->getBus()
      ->isCommandRegistered($event->getCommand())
    ) {
      $l->notice(
        'No class registered to handle /{command_name} command',
        ['command_name' => $event->getCommand()]
      );

      // Unknown command
      $this->dispatchUnknownCommandReceived(
        $event->getUpdate(),
        $event->getCommand(),
        $event->getParameter()
      );

      return;
    }

    // Execute the command
    if ($this->getBot()
      ->getBus()
      ->executeCommand(
        $event->getCommand(),
        $event->getParameter(),
        $event->getUpdate()
      )
    ) {
      // Set flag that request is handled
      $this->getBot()
        ->setRequestHandled(true);

      // Dispatch command is executed
      $this->dispatchCommandExecuted(
        $event->getUpdate(),
        $event->getCommand(),
        $event->getParameter()
      );
    }
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
    $dispatcher = $this->getBot()
      ->getEventDispatcher();

    // Dispatch command event
    $command_unknown_event = new CommandUnknownEvent(
      $update, $command_name, $parameter
    );
    $dispatcher->dispatch(CommandUnknownEvent::NAME, $command_unknown_event);
  }

  /**
   * Dispatches command is executed.
   *
   * @param Update $update
   * @param string $command_name
   * @param string $parameter
   */
  private function dispatchCommandExecuted(
    Update $update,
    $command_name,
    $parameter
  ) {
    $dispatcher = $this->getBot()
      ->getEventDispatcher();

    // Dispatch command event
    $command_executed_event = new CommandExecutedEvent(
      $update, $command_name, $parameter
    );
    $dispatcher->dispatch(CommandExecutedEvent::NAME, $command_executed_event);
  }

  /**
   * Unknown command.
   *
   * @param CommandUnknownEvent $event
   */
  public function onCommandUnknown(CommandUnknownEvent $event)
  {
    // Tell user command is not found
    $this->getBot()
      ->sendMessage(
        $event->getMessage()->chat->id,
        'Извините, такой команды я не знаю.'
      );

    // Set flag that request is handled
    $this->getBot()
      ->setRequestHandled(true);
  }

  /**
   * User has insufficient permissions.
   *
   * @param CommandUnauthorizedEvent $event
   */
  public function onCommandUnauthorized(CommandUnauthorizedEvent $event)
  {
    // Tell the user he is not authorized to execute the command
    $this->getBot()
      ->sendMessage(
        $event->getMessage()->chat->id,
        'Извините, у вас недостаточно прав для доступа к этой команде.'
      );

    // Set flag that request is handled
    $this->getBot()
      ->setRequestHandled(true);
  }
}