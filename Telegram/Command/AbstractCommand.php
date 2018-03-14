<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram\Command;

use Kettari\TelegramBundle\Telegram\CommandBusInterface;
use unreal4u\TelegramAPI\Telegram\Types\Update;

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
   * @var Update
   */
  protected $update;

  /**
   * @var string
   */
  protected $commandParameter = '';

  /**
   * @var \Kettari\TelegramBundle\Telegram\CommandBusInterface
   */
  protected $bus;

  /**
   * @var \Symfony\Component\Translation\TranslatorInterface
   */
  protected $trans;

  /**
   * AbstractCommand constructor.
   *
   * @param \Kettari\TelegramBundle\Telegram\CommandBusInterface $bus
   * @param \unreal4u\TelegramAPI\Telegram\Types\Update $update
   */
  public function __construct(
    CommandBusInterface $bus,
    Update $update
  ) {
    $this->bus = $bus;
    $this->update = $update;
    $this->trans = $bus->getTrans();
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

  /**
   * {@inheritdoc}
   */
  public function initialize(string $commandParameter): TelegramCommandInterface
  {
    $this->commandParameter = $commandParameter;

    return $this;
  }

  /**
   * Executes command.
   *
   * @return void
   */
  abstract public function execute();

  /**
   * {@inheritdoc}
   */
  public function getCommandParameter(): string
  {
    return $this->commandParameter;
  }

}