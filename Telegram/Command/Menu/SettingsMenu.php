<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram\Command\Menu;


use Kettari\TelegramBundle\Telegram\CommandBusInterface;
use unreal4u\TelegramAPI\Telegram\Types\Update;

class SettingsMenu extends AbstractRegularMenu
{
  /**
   * SettingsMenu constructor.
   *
   * @param \Kettari\TelegramBundle\Telegram\CommandBusInterface $bus
   * @param \unreal4u\TelegramAPI\Telegram\Types\Update $update
   */
  public function __construct(CommandBusInterface $bus, Update $update)
  {
    parent::__construct($bus, $update);
    $this->title = 'menu.settings.title';

    // Settings menu options
    // Notifications
    $option = new NotificationsMenuOption($this->bus, $this->update);
    $this->addOption($option);
  }
}