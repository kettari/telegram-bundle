<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram\Event;


use RuntimeException;
use unreal4u\TelegramAPI\Telegram\Types\Update;

abstract class AbstractCommandEvent extends AbstractMessageEvent
{
  /**
   * @var string
   */
  private $commandName = '';

  /**
   * @var string
   */
  private $parameter = '';

  /**
   * CommandReceivedEvent constructor.
   *
   * @param Update $update
   * @param string $commandName
   * @param string $parameter
   */
  public function __construct(
    Update $update,
    string $commandName,
    string $parameter = ''
  ) {
    parent::__construct($update);
    if (empty($this->message->text)) {
      throw new RuntimeException(
        'Text of the Message can\'t be empty for the AbstractCommandEvent.'
      );
    }
    $this->commandName = $commandName;
    $this->parameter = $parameter;
  }

  /**
   * @return string
   */
  public function getCommandName(): string
  {
    return $this->commandName;
  }

  /**
   * @return string
   */
  public function getParameter(): string
  {
    return $this->parameter;
  }
}