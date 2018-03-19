<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram\Command\Menu;

use Kettari\TelegramBundle\Telegram\CommandBusInterface;
use Kettari\TelegramBundle\Telegram\CommunicatorInterface;
use Kettari\TelegramBundle\Telegram\Event\KeeperSingleton;
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
   * @var \Kettari\TelegramBundle\Telegram\Event\KeeperSingleton
   */
  protected $keeper;

  /**
   * @var \Kettari\TelegramBundle\Telegram\Command\Menu\AbstractMenu
   */
  protected $targetMenu;

  /**
   * AbstractMenuOption constructor.
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
    $this->logger = $logger;
    $this->bus = $bus;
    $this->trans = $translator;
    $this->comm = $communicator;
    $this->keeper = KeeperSingleton::getInstance();
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
   * @inheritdoc
   */
  public function checkIsClicked(Update $update): bool
  {
    $this->logger->debug(
      'Checking if option "{callback_id}" is clicked',
      [
        'callback_id' => $this->callbackId,
        'update_id'   => $update->update_id,
        'update_type' => UpdateTypeResolver::getUpdateType($update),
      ]
    );

    // Check if option clicked depending on the update type
    switch (UpdateTypeResolver::getUpdateType($update)) {
      case UpdateTypeResolver::UT_MESSAGE:
        if ($this->trans->trans($this->caption) ==
          $update->message->text) {
          $this->logger->debug(
            'Option "{callback_id}" is clicked with message text',
            ['callback_id' => $this->callbackId]
          );

          return true;
        }
        break;
      case UpdateTypeResolver::UT_CALLBACK_QUERY:
        /*if ($this->callbackId == $this->update->callback_query->data) {
          $this->logger->debug(
            'Option "{callback_id}" is clicked with callback query',
            ['callback_id' => $this->callbackId]
          );

          return true;
        }*/
        break;
    }

    $this->logger->debug(
      'Option "{callback_id}" is not clicked',
      ['callback_id' => $this->callbackId]
    );

    return false;
  }
}