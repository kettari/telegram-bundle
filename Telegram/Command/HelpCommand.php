<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram\Command;


use Kettari\TelegramBundle\Telegram\Communicator;
use unreal4u\TelegramAPI\Telegram\Types\ReplyKeyboardRemove;

class HelpCommand extends AbstractCommand
{

  static public $name = 'help';
  static public $description = 'command.help.description';
  static public $requiredPermissions = ['execute command help'];

  /**
   * Executes command.
   */
  public function execute()
  {
    $text = $this->trans->trans('command.help.list_of_commands').PHP_EOL.
      PHP_EOL;
    $commands = $this->bus->getCommands();
    /** @var AbstractCommand $command */
    foreach ($commands as $command => $placeholder) {
      // Is command visible?
      if (!$command::isVisible()) {
        continue;
      }
      // Has user permissions?
      if (!$this->bus->isAuthorized($this->update->message->from, $command)) {
        // No, user has no permissions
        continue;
      }
      $text .= sprintf(
          '/%s %s',
          $command::$name,
          $this->trans->trans($command::$description)
        ).PHP_EOL;
    }
    $this->replyWithMessage(
      $text,
      Communicator::PARSE_MODE_PLAIN,
      new ReplyKeyboardRemove()
    );
  }

}