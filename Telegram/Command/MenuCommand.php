<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram\Command;


use Kettari\TelegramBundle\Telegram\Command\Menu\MainMenu;
use Kettari\TelegramBundle\Telegram\Command\Menu\SettingsMenu;
use Kettari\TelegramBundle\Telegram\Command\Menu\SettingsMenuOption;
use Kettari\TelegramBundle\Telegram\CommandBusInterface;
use Symfony\Component\Translation\TranslatorInterface;
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
  private $rootMenu;

  /**
   * @inheritDoc
   */
  public function __construct(
    CommandBusInterface $bus,
    Update $update,
    TranslatorInterface $translator
  ) {
    parent::__construct($bus, $update, $translator);

    // Create settings menu
    $settingsMenu = new SettingsMenu($this->bus);

    $settingsOption = new SettingsMenuOption($this->bus);
    $settingsOption->setTargetMenu($settingsMenu);

    $this->rootMenu = new MainMenu($this->bus, 'menu.main.title');
    $this->rootMenu->addOption($settingsOption);
  }

  /**
   * Executes command.
   */
  public function execute()
  {
    $this->rootMenu->show($this->update->message->chat->id);
  }

}