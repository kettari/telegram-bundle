<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram\Subscriber;

use Kettari\TelegramBundle\Telegram\CommandBusInterface;
use Kettari\TelegramBundle\Telegram\CommunicatorInterface;
use Kettari\TelegramBundle\Telegram\PusherInterface;
use Kettari\TelegramBundle\Telegram\UserHqInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

abstract class AbstractBotSubscriber
{
  /**
   * Logger interface.
   *
   * @var LoggerInterface
   */
  protected $logger;

  /**
   * @var RegistryInterface
   */
  protected $doctrine;

  /**
   * @var EventDispatcherInterface
   */
  protected $dispatcher;

  /**
   * @var \Kettari\TelegramBundle\Telegram\UserHqInterface
   */
  protected $userHq;

  /**
   * @var \Kettari\TelegramBundle\Telegram\CommandBusInterface
   */
  protected $bus;

  /**
   * @var CommunicatorInterface
   */
  protected $communicator;

  /**
   * @var PusherInterface
   */
  protected $pusher;

  /**
   * AbstractBotSubscriber constructor.
   *
   * @param \Psr\Log\LoggerInterface $logger
   * @param \Symfony\Bridge\Doctrine\RegistryInterface $doctrine
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher
   * @param \Kettari\TelegramBundle\Telegram\UserHqInterface $userHq
   * @param \Kettari\TelegramBundle\Telegram\CommandBusInterface $bus
   * @param \Kettari\TelegramBundle\Telegram\CommunicatorInterface $communicator
   * @param \Kettari\TelegramBundle\Telegram\PusherInterface $pusher
   */
  public function __construct(
    LoggerInterface $logger,
    RegistryInterface $doctrine,
    EventDispatcherInterface $dispatcher,
    UserHqInterface $userHq,
    CommandBusInterface $bus,
    CommunicatorInterface $communicator,
    PusherInterface $pusher
  ) {
    $this->logger = $logger;
    $this->doctrine = $doctrine;
    $this->dispatcher = $dispatcher;
    $this->userHq = $userHq;
    $this->bus = $bus;
    $this->communicator = $communicator;
    $this->pusher = $pusher;
  }
}