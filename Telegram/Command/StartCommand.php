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

    switch ($this->getParameter()) {
      case 'register':
        // Execute /register
        $this->getBus()
          ->executeCommand('register', null, $this->getUpdate());

        return;
      default:
        break;
    }

    // Standard welcome message
    $this->replyWithMessage('Привет! Список команд доступен по команде /help');

    // Execute /help
    $this->getBus()
      ->executeCommand('help', null, $this->getUpdate());
  }


}