<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram;

use Kettari\TelegramBundle\Telegram\Event\TerminateEvent;
use Kettari\TelegramBundle\Telegram\Event\UpdateReceivedEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use unreal4u\TelegramAPI\Telegram\Types\Update;

class Bot implements BotInterface
{
  /**
   * Logger interface.
   *
   * @var LoggerInterface
   */
  private $logger;

  /**
   * @var EventDispatcherInterface
   */
  private $dispatcher;

  /**
   * @var \Kettari\TelegramBundle\Telegram\CommandBusInterface
   */
  private $bus;

  /**
   * @var \Kettari\TelegramBundle\Telegram\CommunicatorInterface
   */
  private $communicator;

  /**
   * Bot constructor.
   *
   * @param \Psr\Log\LoggerInterface $logger
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher
   * @param \Kettari\TelegramBundle\Telegram\CommandBusInterface $bus
   * @param \Kettari\TelegramBundle\Telegram\CommunicatorInterface $communicator
   */
  public function __construct(
    LoggerInterface $logger,
    EventDispatcherInterface $eventDispatcher,
    CommandBusInterface $bus,
    CommunicatorInterface $communicator
  ) {
    $this->logger = $logger;
    $this->dispatcher = $eventDispatcher;
    $this->bus = $bus;
    $this->communicator = $communicator;
  }

  /**
   * {@inheritdoc}
   */
  public function addSubscriber(EventSubscriberInterface $subscriber)
  {
    $this->dispatcher->addSubscriber($subscriber);
  }

  /**
   * {@inheritdoc}
   */
  public function handle(Update $update)
  {
    $this->logger->debug(
      'Handling update ID={update_id}',
      ['update_id' => $update->update_id]
    );

    // Dispatch event when we got the Update object
    $updateReceivedEvent = new UpdateReceivedEvent($update);
    $this->dispatcher->dispatch(
      UpdateReceivedEvent::NAME,
      $updateReceivedEvent
    );

    // Dispatch termination
    $terminateEvent = new TerminateEvent($update);
    if ($updateReceivedEvent->getResponse()) {
      $terminateEvent->setResponse($updateReceivedEvent->getResponse());
    }
    $this->dispatcher->dispatch(TerminateEvent::NAME, $terminateEvent);

    $this->logger->info(
      'Update ID={update_id} handled',
      ['update_id'          => $update->update_id,
       'is_method_deferred' => $this->communicator->isMethodDeferred(
       ) ? 'yes' : 'no',
      ]
    );

    return $this->communicator->isMethodDeferred(
    ) ? $this->communicator->getDeferredTelegramMethod() : null;
  }
}