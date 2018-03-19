<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram\Command;

use Kettari\TelegramBundle\Telegram\CommandBusInterface;
use Kettari\TelegramBundle\Telegram\CommunicatorInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Translation\TranslatorInterface;

abstract class AbstractCommand implements TelegramCommandInterface
{
  use ReplyWithTrait;

  /**
   * Command name.
   *
   * @var string
   */
  static public $name = '';

  /**
   * Command description.
   *
   * @var string
   */
  static public $description = '';

  /**
   * Array of REGEX patterns this command supports.
   *
   * @var array
   */
  static public $supportedPatterns = [];

  /**
   * If this command is showed in /help?
   *
   * @var bool
   */
  static public $visible = true;

  /**
   * Permissions required to execute this command.
   *
   * @var array
   */
  static public $requiredPermissions = [];

  /**
   * Notifications declared in this command.
   *
   * @var array
   */
  static public $declaredNotifications = [];

  /**
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * @var \Kettari\TelegramBundle\Telegram\CommandBusInterface
   */
  protected $bus;

  /**
   * @var TranslatorInterface
   */
  protected $trans;

  /**
   * @var \Kettari\TelegramBundle\Telegram\CommunicatorInterface
   */
  protected $comm;

  /**
   * AbstractCommand constructor.
   *
   * @param \Psr\Log\LoggerInterface $logger
   * @param \Kettari\TelegramBundle\Telegram\CommandBusInterface $bus
   * @param \Symfony\Component\Translation\TranslatorInterface $translator
   * @param \Kettari\TelegramBundle\Telegram\CommunicatorInterface $communicator
   */
  public function __construct(
    LoggerInterface $logger,
    CommandBusInterface $bus,
    TranslatorInterface $translator,
    CommunicatorInterface $communicator
  ) {
    $this->logger = $logger;
    $this->bus = $bus;
    $this->trans = $translator;
    $this->comm = $communicator;
  }

  /**
   * {@inheritdoc}
   */
  static public function getName(): string
  {
    return static::$name;
  }

  /**
   * {@inheritdoc}
   */
  static public function getDescription(): string
  {
    return static::$description;
  }

  /**
   * {@inheritdoc}
   */
  static public function getSupportedPatterns(): array
  {
    return static::$supportedPatterns;
  }

  /**
   * {@inheritdoc}
   */
  static public function isVisible(): bool
  {
    return static::$visible;
  }

  /**
   * {@inheritdoc}
   */
  static public function getRequiredPermissions(): array
  {
    return static::$requiredPermissions;
  }

  /**
   * {@inheritdoc}
   */
  static public function getDeclaredNotifications(): array
  {
    return static::$declaredNotifications;
  }
}