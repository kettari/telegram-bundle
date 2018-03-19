<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram\Command;

use Kettari\TelegramBundle\Telegram\Communicator;
use unreal4u\TelegramAPI\Telegram\Types\ReplyKeyboardRemove;
use unreal4u\TelegramAPI\Telegram\Types\Update;

class HelpCommand extends AbstractCommand
{

  static public $name = 'help';
  static public $description = 'command.help.description';
  static public $requiredPermissions = ['execute command help'];

  /**
   * @inheritdoc
   */
  public function execute(Update $update, string $parameter = '')
  {
    $text = $this->trans->trans('command.help.list_of_commands').PHP_EOL.
      PHP_EOL;
    $commands = $this->bus->getCommands();
    /** @var \Kettari\TelegramBundle\Telegram\Command\TelegramCommandInterface $command */
    foreach ($commands as $command) {
      // Is command visible?
      if (!$command::isVisible()) {
        continue;
      }
      // Has user permissions?
      if (!$this->bus->isAuthorized($update->message->from, $command)) {
        // No, user has no permissions
        continue;
      }
      $text .= sprintf(
          '/%s %s',
          $command::getName(),
          $this->trans->trans($command::getDescription())
        ).PHP_EOL;
    }
    $this->replyWithMessage(
      $update,
      $text,
      Communicator::PARSE_MODE_PLAIN,
      new ReplyKeyboardRemove()
    );
  }

}