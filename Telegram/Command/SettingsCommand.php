<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram\Command;


use Kettari\TelegramBundle\Telegram\Command\Menu\SettingsMenu;
use Kettari\TelegramBundle\Telegram\CommandBusInterface;
use unreal4u\TelegramAPI\Telegram\Types\Update;

class SettingsCommand extends AbstractCommand
{

  static public $name = 'settings';
  static public $description = 'command.settings.description';
  static public $requiredPermissions = ['execute command settings'];

  /**
   * @var \Kettari\TelegramBundle\Telegram\Command\Menu\SettingsMenu
   */
  private $menu;

  /**
   * @inheritDoc
   */
  public function __construct(
    CommandBusInterface $bus,
    Update $update
  ) {
    parent::__construct($bus, $update);

    $this->menu = new SettingsMenu($this->bus, $this->update);
  }

  /**
   * Executes command.
   */
  public function execute()
  {
    // This command is available only in private chat
    if ('private' != $this->update->message->chat->type) {
      $this->replyWithMessage(
        $this->trans->trans('command.private_only')
      );

      return;
    }

    $this->menu->show();
    $this->menu->hookMySelf();
  }

}