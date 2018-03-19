<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram\Subscriber;


use Psr\Log\LoggerInterface;


abstract class AbstractBotSubscriber
{
  /**
   * Logger interface.
   *
   * @var LoggerInterface
   */
  protected $logger;

  /**
   * AbstractBotSubscriber constructor.
   *
   * @param \Psr\Log\LoggerInterface $logger
   */
  public function __construct(LoggerInterface $logger) {
    $this->logger = $logger;
  }
}