<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram\Command;


use unreal4u\TelegramAPI\Telegram\Types\Update;

class StartCommand extends AbstractCommand
{

  static public $name = 'start';
  static public $description = 'command.start.description';
  static public $visible = false;
  static public $requiredPermissions = ['execute command start'];

  /**
   * @inheritdoc
   */
  public function execute(Update $update, string $parameter = '')
  {
    // Support deep linking like http://t.me/blah_bot?start=commandParameter
    switch ($parameter) {
      case 'register':
        // Execute /register
        if ($this->bus->isCommandRegistered('register')) {
          $this->bus->executeCommand($update, 'register');
        } else {
          $this->replyWithMessage(
            $update,
            $this->trans->trans('command.unknown')
          );
        }

        return;
      default:
        break;
    }

    // Standard welcome message
    if ($this->bus->isCommandRegistered('help')) {
      $this->replyWithMessage(
        $update,
        $this->trans->trans('command.start.welcome')
      );
      $this->bus->executeCommand($update, 'help');
    } else {
      $this->replyWithMessage(
        $update,
        $this->trans->trans('command.unknown')
      );
    }
  }


}