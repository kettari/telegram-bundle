<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram\Command;


use Kettari\TelegramBundle\Telegram\Command\Menu\SettingsMenu;
use Kettari\TelegramBundle\Telegram\CommandBusInterface;
use Kettari\TelegramBundle\Telegram\CommunicatorInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Translation\TranslatorInterface;
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
   * SettingsCommand constructor.
   *
   * @param \Psr\Log\LoggerInterface $logger
   * @param \Kettari\TelegramBundle\Telegram\CommandBusInterface $bus
   * @param \Symfony\Component\Translation\TranslatorInterface $translator
   * @param \Kettari\TelegramBundle\Telegram\CommunicatorInterface $communicator
   * @param \Kettari\TelegramBundle\Telegram\Command\Menu\SettingsMenu $settingsMenu
   */
  public function __construct(
    LoggerInterface $logger,
    CommandBusInterface $bus,
    TranslatorInterface $translator,
    CommunicatorInterface $communicator,
    SettingsMenu $settingsMenu
  ) {
    parent::__construct($logger, $bus, $translator, $communicator);
    $this->menu = $settingsMenu;
  }

  /**
   * @inheritdoc
   */
  public function execute(Update $update, string $parameter = '')
  {
    // This command is available only in private chat
    if ('private' != $update->message->chat->type) {
      $this->replyWithMessage(
        $update,
        $this->trans->trans('command.private_only')
      );

      return;
    }

    $this->menu->show($update);
    $this->menu->hookMySelf($update);
  }

}