<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram\Command;


use Kettari\TelegramBundle\Telegram\Command\Menu\MainMenu;
use Kettari\TelegramBundle\Telegram\CommandBusInterface;
use Kettari\TelegramBundle\Telegram\CommunicatorInterface;
use Psr\Log\LoggerInterface;
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
  private $menu;

  /**
   * MenuCommand constructor.
   *
   * @param \Psr\Log\LoggerInterface $logger
   * @param \Kettari\TelegramBundle\Telegram\CommandBusInterface $bus
   * @param \Symfony\Component\Translation\TranslatorInterface $translator
   * @param \Kettari\TelegramBundle\Telegram\CommunicatorInterface $communicator
   * @param \Kettari\TelegramBundle\Telegram\Command\Menu\MainMenu $mainMenu
   */
  public function __construct(
    LoggerInterface $logger,
    CommandBusInterface $bus,
    TranslatorInterface $translator,
    CommunicatorInterface $communicator,
    MainMenu $mainMenu
  ) {
    parent::__construct($logger, $bus, $translator, $communicator);
    $this->menu = $mainMenu;
  }

  /**
   * @inheritdoc
   */
  public function execute(Update $update, string $parameter = '')
  {
    $this->menu->show($update);
    $this->menu->hookMySelf($update);
  }

}