<?php
/**
 * Created by PhpStorm.
 * User: ant
 * Date: 25.04.2017
 * Time: 14:19
 */

namespace Kaula\TelegramBundle\Telegram\Subscriber;


use Kaula\TelegramBundle\Telegram\Event\CommandReceivedEvent;
use Kaula\TelegramBundle\Telegram\Event\TextReceivedEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use unreal4u\TelegramAPI\Telegram\Types\ReplyKeyboardRemove;
use unreal4u\TelegramAPI\Telegram\Types\Update;

class TextSubscriber extends AbstractBotSubscriber implements EventSubscriberInterface
{
  const EMOJI_TRY_AGAIN = "\xF0\x9F\x99\x83";

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
      TextReceivedEvent::NAME => [
        ['onTextReceived'],
        ['onTextUnhandled', -90000],
      ],
    ];
  }

  /**
   * Processes incoming telegram text.
   *
   * @param TextReceivedEvent $event
   */
  public function onTextReceived(TextReceivedEvent $event)
  {
    $this->parseCommand($event);
  }

  /**
   * Executes event.
   *
   * @param TextReceivedEvent $event
   */
  private function parseCommand(TextReceivedEvent $event)
  {
    /** @var LoggerInterface $l */
    $l = $this->getBot()
      ->getContainer()
      ->get('logger');

    // Parse command "/start@BotName params"
    if (preg_match(
      '/^\/([a-z_]+)@?([a-z_]*)\s*(.*)$/i',
      $event->getText(),
      $matches
    )) {

      if (isset($matches[1]) && ($command_name = $matches[1])) {
        // Parameter?
        $parameter = trim($matches[3]) ?? null;

        $l->info(
          'Detected incoming command /{command}',
          ['command' => $command_name, 'parameter' => $parameter]
        );

        // Dispatch command event
        $this->dispatchCommandReceived(
          $event->getUpdate(),
          $command_name,
          $parameter
        );
      }
    } else {
      $l->debug('No commands detected within the update');
    }
  }

  /**
   * Dispatches command is received.
   *
   * @param Update $update
   * @param string $command_name
   * @param string $parameter
   */
  private function dispatchCommandReceived(
    Update $update,
    $command_name,
    $parameter
  ) {
    $dispatcher = $this->getBot()
      ->getEventDispatcher();

    // Dispatch command event
    $command_received_event = new CommandReceivedEvent(
      $update, $command_name, $parameter
    );
    $dispatcher->dispatch(CommandReceivedEvent::NAME, $command_received_event);
  }

  /**
   * Handles situation when user sent us message and it is not handled.
   *
   * @param \Kaula\TelegramBundle\Telegram\Event\TextReceivedEvent $event
   */
  public function onTextUnhandled(TextReceivedEvent $event)
  {
    if (!$this->getBot()
      ->isRequestHandled()
    ) {
      $l = $this->getBot()
        ->getContainer()
        ->get('logger');
      $l->info('Request was not handled');

      // Tell user we do not understand him/her
      $this->getBot()
        ->sendMessage(
          $event->getMessage()->chat->id,
          self::EMOJI_TRY_AGAIN.' попробуйте начать с команды /help',
          null,
          new ReplyKeyboardRemove()
        );
    }
  }

}