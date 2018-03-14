<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram\Command\Menu;


use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Kettari\TelegramBundle\Telegram\CommandBusInterface;
use Kettari\TelegramBundle\Telegram\UpdateTypeResolver;
use Psr\Log\LoggerInterface;
use Symfony\Component\Translation\TranslatorInterface;
use unreal4u\TelegramAPI\Telegram\Types\Update;

abstract class AbstractMenu implements MenuInterface, HookHandleInterface
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
   * @var Update
   */
  protected $update;

  /**
   * AbstractMenu constructor.
   *
   * @param \Kettari\TelegramBundle\Telegram\CommandBusInterface $bus
   * @param \unreal4u\TelegramAPI\Telegram\Types\Update $update
   */
  public function __construct(CommandBusInterface $bus, Update $update)
  {
    $this->options = new ArrayCollection();
    $this->bus = $bus;
    $this->logger = $bus->getLogger();
    $this->trans = $bus->getTrans();
    $this->update = $update;
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
  public function handler($parameter)
  {
    $this->logger->debug(
      'Menu hook is about to execute',
      [
        'update_id' => $this->update->update_id,
        'update_type' => UpdateTypeResolver::getUpdateType($this->update),
        'parameter' => $parameter,
      ]
    );
    $this->logger->debug(
      'Options count: {options_count} items',
      ['options_count' => $this->options->count()]
    );

    /** @var \Kettari\TelegramBundle\Telegram\Command\Menu\MenuOptionInterface $option */
    foreach ($this->options->toArray() as $option) {
      if ($option->checkIsClicked()) {

        $this->logger->debug('Option "{callback_ud}" ');

        // Let option do whatever is needed when it's clicked
        if ($option->click()) {
          // If option is connected to some menu object, register callback
          $this->bus->createHook(
            $this->update,
            get_class($option->getHandler()),
            'handler'
          );
        }

        break;
      }
    }

    $this->logger->info(
      'Menu hook executed',
      ['update_id' => $this->update->update_id]
    );
  }

  /**
   * @inheritdoc
   */
  public function hookMySelf()
  {
    $this->bus->createHook($this->update, get_class($this));
  }

}