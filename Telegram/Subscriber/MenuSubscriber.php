<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram\Subscriber;


use Kettari\TelegramBundle\Telegram\Event\TerminateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MenuSubscriber extends AbstractBotSubscriber implements EventSubscriberInterface
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
    return [TerminateEvent::NAME => 'onRequestTermination'];
  }

  /**
   * @param \Kettari\TelegramBundle\Telegram\Event\TerminateEvent $event
   */
  public function onRequestTermination(TerminateEvent $event)
  {
    $this->logger->debug(
      'Processing MenuSubscriber::TerminateEvent, request status: {request_status}',
      ['update_id' => $event->getUpdate()->update_id,]
    );

    if (!$event->getKeeper()
        ->isRequestHandled() && $this->bus->isCommandRegistered('menu')) {
      $this->logger->debug('Request was not handled, executing menu command');
      $this->bus->executeCommand($event->getUpdate(), 'menu');
    }

    $this->logger->info(
      'MenuSubscriber::TerminateEvent processed',
      ['update_id' => $event->getUpdate()->update_id]
    );
  }

}