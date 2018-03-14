<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram\Command\Menu;


use Kettari\TelegramBundle\Telegram\CommandBusInterface;

class SettingsMenu extends AbstractRegularMenu
{
  /**
   * SettingsMenu constructor.
   *
   * @param \Kettari\TelegramBundle\Telegram\CommandBusInterface $bus
   */
  public function __construct(CommandBusInterface $bus)
  {
    parent::__construct($bus, 'menu.settings.title');


  }
}