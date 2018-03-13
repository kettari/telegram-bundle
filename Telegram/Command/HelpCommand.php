<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram\Command;


class HelpCommand extends AbstractCommand
{

  static public $name = 'help';
  static public $description = 'Показать список команд бота';
  static public $requiredPermissions = ['execute command help'];

  /**
   * Executes command.
   */
  public function execute()
  {
    $text = 'Список команд бота: '.PHP_EOL.PHP_EOL;
    $commands = $this->bus->getCommands();
    /** @var AbstractCommand $command */
    foreach ($commands as $command => $placeholder) {
      // Is it visible?
      if (!$command::isVisible()) {
        continue;
      }
      // Has user permissions?
      if (!$this->bus->isAuthorized($this->update->message->from, $command)) {
        continue;
      }
      $text .= sprintf('/%s %s', $command::$name, $command::$description).
        PHP_EOL;
    }
    $this->replyWithMessage($text);
  }


}