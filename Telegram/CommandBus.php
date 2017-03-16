<?php
/**
 * Created by PhpStorm.
 * User: ant
 * Date: 16.03.2017
 * Time: 18:11
 */

namespace Kaula\TelegramBundle\Telegram;


use Kaula\TelegramBundle\Exception\InvalidCommand;
use Kaula\TelegramBundle\Telegram\Command\AbstractCommand;
use unreal4u\TelegramAPI\Telegram\Types\Update;

class CommandBus {

  protected $commands_classes = [];

  /**
   * Registers command.
   *
   * @param $command_class
   * @return CommandBus
   */
  public function registerCommand($command_class) {
    if (is_subclass_of($command_class, AbstractCommand::class)) {
      $this->commands_classes[$command_class] = TRUE;
    } else {
      throw new InvalidCommand('Unable to register command: '.$command_class.
        ' The command should be a subclass of '.AbstractCommand::class);
    }

    return $this;
  }

  /**
   * Executes command that is registered with CommandBus.
   *
   * @param $command_name
   * @param \unreal4u\TelegramAPI\Telegram\Types\Update $update
   * @return bool Returns TRUE if appropriate command was found.
   */
  public function executeCommand($command_name, Update $update) {
    foreach ($this->commands_classes as $commands_class => $placeholder) {
      if (class_exists($commands_class)) {
        /** @var AbstractCommand $command */
        $command = new $commands_class($this, $update);
        $command->execute();

        return TRUE;
      }
    }

    return FALSE;
  }

}