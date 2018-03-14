<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram\Command\Menu;


use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Kettari\TelegramBundle\Exception\MenuException;
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
   * AbstractMenu constructor.
   *
   * @param \Kettari\TelegramBundle\Telegram\CommandBusInterface $bus
   * @param string $title Menu title which is displayed with menu options.
   */
  public function __construct(CommandBusInterface $bus, string $title)
  {
    if (empty($this->title)) {
      throw new MenuException('Menu title can\'t be empty.');
    }
    $this->options = new ArrayCollection();
    $this->bus = $bus;
    $this->logger = $bus->getLogger();
    $this->trans = $bus->getTrans();
    $this->title = $title;
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
   * @inheritdoc
   */
  public function handler(Update $update, $parameter)
  {
    $this->logger->debug(
      'Menu hook for the update ID={update_id} type "{update_type}" is about to execute',
      [
        'update_id'   => $update->update_id,
        'update_type' => UpdateTypeResolver::getUpdateType($update),
        'update'      => $update,
      ]
    );

    /** @var \Kettari\TelegramBundle\Telegram\Command\Menu\MenuOptionInterface $option */
    foreach ($this->options as $option) {
      if ($option->checkIsClicked($update)) {

        // Let option do whatever is needed when it's clicked
        if ($option->click($parameter)) {
          // If option is connected to some menu object, register callback
          $this->bus->createHook(
            $update,
            get_class($option->getHandler()),
            'handler'
          );
        }

        break;
      }
    }

    $this->logger->info(
      'Menu hook for update ID={update_id} executed',
      ['update_id' => $update->update_id]
    );
  }

}