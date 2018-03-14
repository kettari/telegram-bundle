<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram\Command\Menu;

use Kettari\TelegramBundle\Telegram\CommandBusInterface;
use Kettari\TelegramBundle\Telegram\UpdateTypeResolver;
use Psr\Log\LoggerInterface;
use Symfony\Component\Translation\TranslatorInterface;
use unreal4u\TelegramAPI\Telegram\Types\Update;

abstract class AbstractMenuOption implements MenuOptionInterface, HookHandleInterface
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
   * @var \Kettari\TelegramBundle\Telegram\CommunicatorInterface
   */
  protected $comm;

  /**
   * @var TranslatorInterface
   */
  protected $trans;

  /**
   * @var string
   */
  protected $caption = '';

  /**
   * @var string
   */
  protected $callbackId = '';

  /**
   * @var \Kettari\TelegramBundle\Telegram\Command\Menu\AbstractMenu
   */
  protected $targetMenu;

  /**
   * AbstractMenuOption constructor.
   *
   * @param \Kettari\TelegramBundle\Telegram\CommandBusInterface $bus
   * @param string $caption
   * @param string $callbackId
   */
  public function __construct(
    CommandBusInterface $bus,
    string $caption,
    string $callbackId
  ) {
    $this->bus = $bus;
    $this->logger = $bus->getLogger();
    $this->comm = $bus->getCommunicator();
    $this->trans = $bus->getTrans();
    $this->caption = $caption;
    $this->callbackId = $callbackId;
  }

  /**
   * @inheritdoc
   */
  public function getHandler(): HookHandleInterface
  {
    return $this->targetMenu ? $this->targetMenu : $this;
  }

  /**
   * @param MenuInterface $targetMenu
   * @return AbstractMenuOption
   */
  public function setTargetMenu(MenuInterface $targetMenu): AbstractMenuOption
  {
    $this->targetMenu = $targetMenu;

    return $this;
  }

  /**
   * @inheritdoc
   */
  public function getCaption(): string
  {
    return $this->caption;
  }

  /**
   * @inheritdoc
   */
  public function getCallbackId(): string
  {
    return $this->callbackId;
  }

  /**
   * @param \unreal4u\TelegramAPI\Telegram\Types\Update $update
   * @return bool
   */
  public function checkIsClicked(Update $update): bool
  {
    $this->logger->debug(
      'Checking if option "{callback_id}" is clicked for the update ID={update_id} type "{update_type}"',
      [
        'callback_id' => $this->callbackId,
        'update_id'   => $update->update_id,
        'update_type' => UpdateTypeResolver::getUpdateType($update),
      ]
    );

    // Check if option clicked depending on the update type
    switch (UpdateTypeResolver::getUpdateType($update)) {
      case UpdateTypeResolver::UT_MESSAGE:
        if ($this->trans->trans($this->caption) == $update->message->text) {
          return true;
        }
        break;
      case UpdateTypeResolver::UT_CALLBACK_QUERY:
        if ($this->callbackId == $update->callback_query->id) {
          return true;
        }
        break;
    }

    return false;
  }

  /**
   * @inheritdoc
   */
  abstract public function click(Update $update): bool;
}