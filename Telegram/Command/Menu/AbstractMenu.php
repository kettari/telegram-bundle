<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram\Command\Menu;


use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Kettari\TelegramBundle\Telegram\CommandBusInterface;
use Kettari\TelegramBundle\Telegram\CommunicatorInterface;
use Kettari\TelegramBundle\Telegram\Event\KeeperSingleton;
use Kettari\TelegramBundle\Telegram\UpdateTypeResolver;
use Psr\Log\LoggerInterface;
use Symfony\Component\Translation\TranslatorInterface;
use unreal4u\TelegramAPI\Telegram\Types\Update;

abstract class AbstractMenu implements MenuInterface
{
  /**
   * @var CommandBusInterface
   */
  protected $bus;

  /**
   * @var LoggerInterface
   */
  protected $logger;

  /**
   * @var TranslatorInterface
   */
  protected $trans;

  /**
   * @var CommunicatorInterface
   */
  protected $communicator;

  /**
   * @var MenuInterface
   */
  protected $parentMenu;

  /**
   * @var Collection
   */
  protected $options;

  /**
   * @var string
   */
  protected $title = '';

  /**
   * @var \Kettari\TelegramBundle\Telegram\Event\KeeperSingleton
   */
  protected $keeper;

  /**
   * AbstractMenu constructor.
   *
   * @param \Psr\Log\LoggerInterface $logger
   * @param \Kettari\TelegramBundle\Telegram\CommandBusInterface $bus
   * @param \Symfony\Component\Translation\TranslatorInterface $translator
   * @param \Kettari\TelegramBundle\Telegram\CommunicatorInterface $communicator
   */
  public function __construct(
    LoggerInterface $logger,
    CommandBusInterface $bus,
    TranslatorInterface $translator,
    CommunicatorInterface $communicator
  ) {
    $this->options = new ArrayCollection();
    $this->logger = $logger;
    $this->bus = $bus;
    $this->trans = $translator;
    $this->communicator = $communicator;
    $this->keeper = KeeperSingleton::getInstance();
  }

  /**
   * @return MenuInterface
   */
  public function getParentMenu()
  {
    return $this->parentMenu;
  }

  /**
   * @param MenuInterface $parentMenu
   * @return AbstractMenu
   */
  public function setParentMenu(MenuInterface $parentMenu): AbstractMenu
  {
    $this->parentMenu = $parentMenu;

    return $this;
  }

  /**
   * @return Collection
   */
  public function getOptions(): Collection
  {
    return $this->options;
  }

  /**
   * @param \Kettari\TelegramBundle\Telegram\Command\Menu\MenuOptionInterface $option
   * @return \Kettari\TelegramBundle\Telegram\Command\Menu\AbstractMenu
   */
  public function addOption(MenuOptionInterface $option): AbstractMenu
  {
    $this->options->add($option);

    return $this;
  }

  /**
   * @return string
   */
  public function getTitle(): string
  {
    return $this->title;
  }

  /**
   * @param string $title
   * @return AbstractMenu
   */
  public function setTitle(string $title): AbstractMenu
  {
    $this->title = $title;

    return $this;
  }

  /**
   * @inheritdoc
   */
  public function handler(Update $update, $parameter)
  {
    $this->logger->debug(
      'Menu hook is about to execute',
      [
        'update_id'   => $update->update_id,
        'update_type' => UpdateTypeResolver::getUpdateType($update),
        'parameter'   => $parameter,
      ]
    );
    $this->logger->debug(
      'Options count: {options_count} items',
      ['options_count' => $this->options->count()]
    );

    /** @var \Kettari\TelegramBundle\Telegram\Command\Menu\MenuOptionInterface $option */
    foreach ($this->options->toArray() as $option) {
      if ($option->checkIsClicked($update)) {
        // Let option do whatever is needed when it's clicked
        if ($option->click($update)) {
          $option->hookMySelf($update);
          $this->keeper->setRequestHandled(true);
        }
        break;
      }
    }

    $this->logger->info(
      'Menu hook executed',
      ['update_id' => $update->update_id]
    );
  }
}