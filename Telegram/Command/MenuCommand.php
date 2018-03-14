<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram\Command;


use Kettari\TelegramBundle\Telegram\Command\Menu\MainMenu;
use Kettari\TelegramBundle\Telegram\CommandBusInterface;
use unreal4u\TelegramAPI\Telegram\Types\Update;

class MenuCommand extends AbstractCommand
{

  static public $name = 'menu';
  static public $description = 'command.menu.description';
  static public $visible = false;
  static public $requiredPermissions = ['execute command menu'];

  /**
   * @var \Kettari\TelegramBundle\Telegram\Command\Menu\MainMenu
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

    $this->menu = new MainMenu($this->bus, $this->update);
  }

  /**
   * Executes command.
   */
  public function execute()
  {
    $this->menu->show();
    $this->menu->hookMySelf();
  }

}