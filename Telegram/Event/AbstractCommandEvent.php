<?php
/**
 * Created by PhpStorm.
 * User: ant
 * Date: 25.04.2017
 * Time: 13:15
 */

namespace Kettari\TelegramBundle\Telegram\Event;


use RuntimeException;
use unreal4u\TelegramAPI\Telegram\Types\Update;

abstract class AbstractCommandEvent extends AbstractMessageEvent
{
  /**
   * @var string
   */
  private $command;

  /**
   * @var string
   */
  private $parameter;

  /**
   * CommandReceivedEvent constructor.
   *
   * @param Update $update
   * @param string $command
   * @param string $parameter
   */
  public function __construct(Update $update, $command, $parameter)
  {
    if (is_null($update->message)) {
      throw new RuntimeException(
        'Message can\'t be null for the AbstractCommandEvent.'
      );
    }
    if (empty($update->message->text)) {
      throw new RuntimeException(
        'Text of the Message can\'t be empty for the AbstractCommandEvent.'
      );
    }

    $this->setMessage($update->message)
      ->setUpdate($update);
    $this->command = $command;
    $this->parameter = $parameter;
  }

  /**
   * @return string
   */
  public function getText()
  {
    return $this->getMessage()->text;
  }

  /**
   * @return string
   */
  public function getCommand()
  {
    return $this->command;
  }

  /**
   * @return string
   */
  public function getParameter()
  {
    return $this->parameter;
  }
}