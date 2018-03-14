<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram\Command\Menu;


use Kettari\TelegramBundle\Telegram\CommandBusInterface;
use Kettari\TelegramBundle\Telegram\Communicator;
use Kettari\TelegramBundle\Telegram\TelegramObjectsRetrieverTrait;
use unreal4u\TelegramAPI\Telegram\Types\Update;

class SettingsMenuOption extends AbstractMenuOption
{
  use TelegramObjectsRetrieverTrait;

  /**
   * @param \Kettari\TelegramBundle\Telegram\CommandBusInterface $bus
   * @param \unreal4u\TelegramAPI\Telegram\Types\Update $update
   */
  public function __construct(CommandBusInterface $bus, Update $update)
  {
    parent::__construct($bus, $update);
    $this->caption = 'menu.settings.button_caption';
    $this->callbackId = 'menu.settings';
  }

  /**
   * @inheritDoc
   */
  public function click(): bool
  {
    $this->logger->debug('Clicking settings option');

    if (is_null($tgMessage = $this->getMessageFromUpdate($this->update))) {
      return false;
    }

    // Execute command /settings
    if ($this->bus->isCommandRegistered('settings')) {
      $this->bus->executeCommand($this->update, 'settings');

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
  public function handler($parameter)
  {
    // TODO: Implement handler() method.
  }
}