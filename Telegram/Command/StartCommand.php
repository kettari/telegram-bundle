<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram\Command;


class StartCommand extends AbstractCommand
{

  static public $name = 'start';
  static public $description = 'command.start.description';
  static public $visible = false;
  static public $requiredPermissions = ['execute command start'];

  /**
   * Executes command.
   */
  public function execute()
  {
    // Support deep linking like http://t.me/blah_bot?start=commandParameter
    switch ($this->getCommandParameter()) {
      case 'register':
        // Execute /register
        if ($this->bus->isCommandRegistered('register')) {
          $this->bus->executeCommand($this->update, 'register');
        }

        return;
      default:
        break;
    }

    // Standard welcome message
    if ($this->bus->isCommandRegistered('help')) {

      $this->replyWithMessage(
        $this->trans->trans('command.start.welcome')
      );
      $this->bus->executeCommand($this->update, 'help');

    }
  }


}