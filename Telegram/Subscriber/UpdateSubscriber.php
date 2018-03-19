<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram\Subscriber;


use Kettari\TelegramBundle\Telegram\Event\MessageReceivedEvent;
use Kettari\TelegramBundle\Telegram\Event\UpdateReceivedEvent;
use Kettari\TelegramBundle\Telegram\UpdateTypeResolver;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;


class UpdateSubscriber extends AbstractBotSubscriber implements EventSubscriberInterface
{
  /**
   * @var EventDispatcherInterface
   */
  private $dispatcher;

  /**
   * HookerSubscriber constructor.
   *
   * @param \Psr\Log\LoggerInterface $logger
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher
   */
  public function __construct(
    LoggerInterface $logger,
    EventDispatcherInterface $dispatcher
  ) {
    parent::__construct($logger);
    $this->dispatcher = $dispatcher;
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
      UpdateReceivedEvent::NAME => ['onUpdateReceived'],
    ];
  }

  /**
   * Processes update and tries to dispatch Message event.
   *
   * @param \Kettari\TelegramBundle\Telegram\Event\UpdateReceivedEvent $event
   */
  public function onUpdateReceived(UpdateReceivedEvent $event)
  {
    // Get update type
    $updateType = UpdateTypeResolver::getUpdateType($event->getUpdate());
    $this->logger->debug(
      'Handling update of the type "{type}" for update ID={update_id}',
      [
        'type'      => $updateType,
        'update_id' => $event->getUpdate()->update_id,
        'update'    => $event->getUpdate(),
      ]
    );

    // Check type of the update and dispatch more specific events
    switch ($updateType) {
      case UpdateTypeResolver::UT_MESSAGE:
        $messageReceivedEvent = new MessageReceivedEvent($event->getUpdate());
        $this->dispatcher->dispatch(
          MessageReceivedEvent::NAME,
          $messageReceivedEvent
        );
        break;
    }

    $this->logger->info(
      'Update of the type "{type}" handled for update ID={update_id}',
      [
        'type'      => $updateType,
        'update_id' => $event->getUpdate()->update_id,
        'update'    => $event->getUpdate(),
      ]
    );
  }
}