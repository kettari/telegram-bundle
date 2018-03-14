<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram\Command\Menu;


use Kettari\TelegramBundle\Telegram\CommandBusInterface;
use Kettari\TelegramBundle\Telegram\Communicator;
use Kettari\TelegramBundle\Telegram\TelegramObjectsRetrieverTrait;
use unreal4u\TelegramAPI\Telegram\Types\Update;

class HelpMenuOption extends AbstractMenuOption
{
  use TelegramObjectsRetrieverTrait;

  /**
   * @param \Kettari\TelegramBundle\Telegram\CommandBusInterface $bus
   * @param \unreal4u\TelegramAPI\Telegram\Types\Update $update
   */
  public function __construct(CommandBusInterface $bus, Update $update)
  {
    parent::__construct($bus, $update);
    $this->caption = 'menu.help.button_caption';
    $this->callbackId = 'menu.help';
  }

  /**
   * @inheritDoc
   */
  public function click(): bool
  {
    $this->logger->debug('Clicking help option');

    if (is_null($tgMessage = $this->getMessageFromUpdate($this->update))) {
      return false;
    }

    // Execute command /help
    if ($this->bus->isCommandRegistered('help')) {
      $this->bus->executeCommand($this->update, 'help');
    } else {
      $this->comm->sendMessage(
        $tgMessage->chat->id,
        $this->trans->trans('command.unknown'),
        Communicator::PARSE_MODE_PLAIN
      );
    }

    $this->logger->info('Clicked help option');

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