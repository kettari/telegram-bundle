<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram\Subscriber;


use Kettari\TelegramBundle\Telegram\Event\CommandReceivedEvent;
use Kettari\TelegramBundle\Telegram\Event\TextReceivedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use unreal4u\TelegramAPI\Telegram\Types\Update;

class TextSubscriber extends AbstractBotSubscriber implements EventSubscriberInterface
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
      TextReceivedEvent::NAME => ['onTextReceived', 80000],
    ];
  }

  /**
   * Processes incoming telegram text.
   *
   * @param TextReceivedEvent $event
   */
  public function onTextReceived(TextReceivedEvent $event)
  {
    $this->logger->debug(
      'Processing TextSubscriber::TextReceivedEvent for message ID={message_id} in the chat ID={chat_id}',
      [
        'message_id' => $event->getMessage()->message_id,
        'chat_id'    => $event->getMessage()->chat->id,
      ]
    );

    /** @noinspection PhpStatementHasEmptyBodyInspection */
    if (!$this->parseCommand($event)) {
      // Not a command. Well, do nothing
    }

    $this->logger->info(
      'TextSubscriber::TextReceivedEvent for message ID={message_id} in the chat ID={chat_id} processed',
      [
        'message_id' => $event->getMessage()->message_id,
        'chat_id'    => $event->getMessage()->chat->id,
      ]
    );
  }

  /**
   * Executes event.
   *
   * @param TextReceivedEvent $event
   * @return bool
   */
  private function parseCommand(TextReceivedEvent $event)
  {
    // Parse command "/start@BotName params"
    if (preg_match(
      '/^\/([a-z_]+)@?([a-z_]*)\s*(.*)$/i',
      $event->getText(),
      $matches
    )) {

      if (isset($matches[1]) && ($commandName = $matches[1])) {
        // Parameter?
        $parameter = trim($matches[3]) ?? '';

        $this->logger->info(
          'Detected incoming command /{command}',
          ['command' => $commandName, 'parameter' => $parameter]
        );

        // Dispatch command event
        $this->dispatchCommandReceived(
          $event->getUpdate(),
          $commandName,
          $parameter
        );

        return true;
      }
    }

    // Add some logging
    $this->logger->info('No commands detected within the update');

    return false;
  }

  /**
   * Dispatches command is received.
   *
   * @param Update $update
   * @param string $commandName
   * @param string $parameter
   */
  private function dispatchCommandReceived(
    Update $update,
    string $commandName,
    string $parameter
  ) {
    // Dispatch command event
    $command_received_event = new CommandReceivedEvent(
      $update, $commandName, $parameter
    );
    $this->dispatcher->dispatch(
      CommandReceivedEvent::NAME,
      $command_received_event
    );
  }

}