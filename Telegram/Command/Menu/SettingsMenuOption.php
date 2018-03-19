<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram\Command\Menu;


use Kettari\TelegramBundle\Exception\TelegramBundleException;
use Kettari\TelegramBundle\Telegram\CommandBusInterface;
use Kettari\TelegramBundle\Telegram\Communicator;
use Kettari\TelegramBundle\Telegram\CommunicatorInterface;
use Kettari\TelegramBundle\Telegram\TelegramObjectsRetrieverTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\Translation\TranslatorInterface;
use unreal4u\TelegramAPI\Telegram\Types\Update;

class SettingsMenuOption extends AbstractMenuOption
{
  use TelegramObjectsRetrieverTrait;

  /**
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
    parent::__construct($logger, $bus, $translator, $communicator);
    $this->caption = 'menu.settings.button_caption';
    $this->callbackId = 'menu.settings';
  }

  /**
   * @inheritDoc
   */
  public function click(Update $update): bool
  {
    $this->logger->debug('Clicking settings option');

    if (is_null($tgMessage = $this->getMessageFromUpdate($update))) {
      return false;
    }

    // Execute command /settings
    if ($this->bus->isCommandRegistered('settings')) {
      $this->bus->executeCommand($update, 'settings');

      // Mark request as handled to prevent home menu
      $this->keeper->setRequestHandled(true);
    } else {
      $this->comm->sendMessage(
        $tgMessage->chat->id,
        $this->trans->trans('command.unknown'),
        Communicator::PARSE_MODE_PLAIN
      );
    }

    $this->logger->info('Clicked settings option');

    return false;
  }

  /**
   * @inheritDoc
   */
  public function handler(Update $update, $parameter)
  {
    throw new TelegramBundleException(
      'Settings option is not expected to be called with handler.'
    );
  }

  /**
   * @inheritDoc
   */
  public function hookMySelf(Update $update)
  {
    throw new TelegramBundleException(
      'Settings option is not expected to be self-hooked.'
    );
  }
}