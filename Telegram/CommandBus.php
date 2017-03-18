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
use Psr\Log\LoggerInterface;


use unreal4u\TelegramAPI\Telegram\Types\Update;

class CommandBus {

  /**
   * Bot this command bus belongs to.
   *
   * @var Bot
   */
  protected $bot;

  /**
   * @var Hooker
   */
  protected $hooker;

  /**
   * Commands classes.
   *
   * @var array
   */
  protected $commands_classes = [];

  /**
   * CommandBus constructor.
   *
   * @param \Kaula\TelegramBundle\Telegram\Bot $bot
   * @param \Kaula\TelegramBundle\Telegram\Hooker $hooker
   */
  public function __construct(Bot $bot, Hooker $hooker = NULL) {
    $this->bot = $bot;
    if (!is_null($hooker)) {
      $this->hooker = $hooker;
    } else {
      $this->hooker = new Hooker($this);
    }
  }

  /**
   * Registers command.
   *
   * @param string $command_class
   * @return \Kaula\TelegramBundle\Telegram\CommandBus
   */
  public function registerCommand($command_class) {
    if (class_exists($command_class)) {
      if (is_subclass_of($command_class, AbstractCommand::class)) {
        $this->commands_classes[$command_class] = TRUE;
      } else {
        throw new InvalidCommand('Unable to register command: '.$command_class.
          ' The command should be a subclass of '.AbstractCommand::class);
      }
    } else {
      throw new InvalidCommand('Unable to register command: '.$command_class.
        ' Class is not found');
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
    /** @var LoggerInterface $l */
    $l = $this->getBot()
      ->getContainer()
      ->get('logger');

    foreach ($this->commands_classes as $command_class => $placeholder) {
      /** @var AbstractCommand $command_class */
      if ($command_class::getName() == $command_name) {
        $l->debug('Executing /{command_name} command with the class "{class_name}"',
          ['command_name' => $command_name, 'class_name' => $command_class]);

        /** @var AbstractCommand $command */
        $command = new $command_class($this, $update);
        $command->initialize()
          ->execute();

        return TRUE;
      }
    }
    $l->debug('No class registered to handle /{command_name} command',
      ['command_name' => $command_name]);

    return FALSE;
  }

  /**
   * Returns array of commands classes.
   *
   * @return array
   */
  public function getCommands() {
    return $this->commands_classes;
  }

  /**
   * Returns bot object.
   *
   * @return \Kaula\TelegramBundle\Telegram\Bot
   */
  public function getBot() {
    return $this->bot;
  }

  /**
   * @return Hooker
   */
  public function getHooker(): Hooker {
    return $this->hooker;
  }

}