<?php
/**
 * Created by PhpStorm.
 * User: ant
 * Date: 16.03.2017
 * Time: 18:18
 */

namespace Kaula\TelegramBundle\Telegram\Command;


class HelpCommand extends AbstractCommand
{

  static public $name = 'help';
  static public $description = 'Показать список команд бота';
  static public $required_permissions = ['execute command help'];

  /**
   * Executes command.
   */
  public function execute()
  {
    $text = 'Список команд бота: '.PHP_EOL.PHP_EOL;
    $commands = $this->getBus()
      ->getCommands();
    /** @var AbstractCommand $command */
    foreach ($commands as $command => $placeholder) {
      // Is it visible?
      if (!$command::isVisible()) {
        continue;
      }
      // Has user permissions?
      if (!$this->getBus()
        ->isAuthorized($this->getUpdate()->message->from, $command)
      ) {
        continue;
      }
      $text .= sprintf('/%s %s', $command::$name, $command::$description).
        PHP_EOL;
    }
    $this->replyWithMessage($text);
  }


}