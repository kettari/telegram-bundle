<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram\Command;


class StartCommand extends AbstractCommand
{

  static public $name = 'start';
  static public $description = 'Начать разговор с ботом';
  static public $visible = false;
  static public $requiredPermissions = ['execute command start'];

  /**
   * Executes command.
   */
  public function execute()
  {
    /*if (!empty($this->getParameter())) {
      $this->replyWithMessage('Параметр: '.$this->getParameter());
    }*/

    switch ($this->getCommandParameter()) {
      case 'register':
        // Execute /register
        $this->bus->executeCommand($this->update, 'register');

        return;
      default:
        break;
    }

    // Standard welcome message
    $this->replyWithMessage('Привет! Список команд доступен по команде /help');

    // Execute /help
    $this->bus->executeCommand($this->update, 'help');
  }


}