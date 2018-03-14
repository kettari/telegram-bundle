<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram\Command\Menu;


use Kettari\TelegramBundle\Telegram\CommandBusInterface;
use unreal4u\TelegramAPI\Telegram\Types\Update;

class MainMenu extends AbstractRegularMenu
{
  /**
   * @inheritDoc
   */
  public function __construct(CommandBusInterface $bus, Update $update)
  {
    parent::__construct($bus, $update);
    $this->title = 'menu.main.title';

    // Create settings menu
    $settingsMenu = new SettingsMenu($this->bus, $this->update);
    $settingsMenu->setParentMenu($this);

    // Main menu options
    // Settings
    $helpOption = new HelpMenuOption($this->bus, $this->update);
    $this->addOption($helpOption);

    // Settings
    $settingsOption = new SettingsMenuOption($this->bus, $this->update);
    $settingsOption->setTargetMenu($settingsMenu);
    $this->addOption($settingsOption);
  }


}