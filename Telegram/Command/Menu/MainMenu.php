<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram\Command\Menu;


use Kettari\TelegramBundle\Exception\TelegramBundleException;
use Kettari\TelegramBundle\Telegram\CommandBusInterface;
use Kettari\TelegramBundle\Telegram\CommunicatorInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Translation\TranslatorInterface;
use unreal4u\TelegramAPI\Telegram\Types\Update;

class MainMenu extends AbstractRegularMenu
{
  /**
   * MainMenu constructor.
   *
   * @param \Psr\Log\LoggerInterface $logger
   * @param \Kettari\TelegramBundle\Telegram\CommandBusInterface $bus
   * @param \Symfony\Component\Translation\TranslatorInterface $translator
   * @param \Kettari\TelegramBundle\Telegram\CommunicatorInterface $communicator
   * @param \Kettari\TelegramBundle\Telegram\Command\Menu\SettingsMenu $settingsMenu
   * @param \Kettari\TelegramBundle\Telegram\Command\Menu\HelpMenuOption $helpMenuOption
   * @param \Kettari\TelegramBundle\Telegram\Command\Menu\SettingsMenuOption $settingsMenuOption
   */
  public function __construct(
    LoggerInterface $logger,
    CommandBusInterface $bus,
    TranslatorInterface $translator,
    CommunicatorInterface $communicator,
    SettingsMenu $settingsMenu,
    HelpMenuOption $helpMenuOption,
    SettingsMenuOption $settingsMenuOption
  )
  {
    parent::__construct($logger, $bus, $translator, $communicator);
    $this->title = 'menu.main.title';

    // Main menu options
    $this->addOption($helpMenuOption);
    $settingsMenuOption->setTargetMenu($settingsMenu);
    $this->addOption($settingsMenuOption);
  }

  /**
   * @inheritDoc
   */
  public function hookMySelf(Update $update)
  {
    $this->bus->createHook($update, 'kettari_telegram.menu.main');
  }


}