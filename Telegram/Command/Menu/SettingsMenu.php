<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram\Command\Menu;


use Kettari\TelegramBundle\Telegram\CommandBusInterface;
use Kettari\TelegramBundle\Telegram\CommunicatorInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Translation\TranslatorInterface;
use unreal4u\TelegramAPI\Telegram\Types\Update;

class SettingsMenu extends AbstractRegularMenu
{
  /**
   * SettingsMenu constructor.
   *
   * @param \Psr\Log\LoggerInterface $logger
   * @param \Kettari\TelegramBundle\Telegram\CommandBusInterface $bus
   * @param \Symfony\Component\Translation\TranslatorInterface $translator
   * @param \Kettari\TelegramBundle\Telegram\CommunicatorInterface $communicator
   * @param \Kettari\TelegramBundle\Telegram\Command\Menu\NotificationsMenuOption $notificationsMenuOption
   */
  public function __construct(
    LoggerInterface $logger,
    CommandBusInterface $bus,
    TranslatorInterface $translator,
    CommunicatorInterface $communicator,
    NotificationsMenuOption $notificationsMenuOption
  ) {
    parent::__construct($logger, $bus, $translator, $communicator);
    $this->title = 'menu.settings.title';

    // Settings menu options
    $this->appendOption($notificationsMenuOption);
  }

  /**
   * @inheritDoc
   */
  public function hookMySelf(Update $update)
  {
    $this->bus->createHook($update, 'kettari_telegram.menu.settings');
  }


}