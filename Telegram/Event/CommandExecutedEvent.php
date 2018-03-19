<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram\Event;

use Kettari\TelegramBundle\Telegram\Command\TelegramCommandInterface;
use unreal4u\TelegramAPI\Telegram\Types\Update;

class CommandExecutedEvent extends AbstractCommandEvent
{
  const NAME = 'telegram.command.executed';

  /**
   * @var \Kettari\TelegramBundle\Telegram\Command\TelegramCommandInterface
   */
  private $command;

  /**
   * CommandReceivedEvent constructor.
   *
   * @param Update $update
   * @param \Kettari\TelegramBundle\Telegram\Command\TelegramCommandInterface $command
   */
  public function __construct(
    Update $update,
    TelegramCommandInterface $command
  ) {
    parent::__construct($update, $command::getName());
    $this->command = $command;
  }

  /**
   * @return \Kettari\TelegramBundle\Telegram\Command\AbstractCommand
   */
  public function getCommand(): TelegramCommandInterface
  {
    return $this->command;
  }
}