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
   */
  public function __construct(CommandBusInterface $bus)
  {
    parent::__construct($bus, 'menu.settings.caption', 'menu.settings');
  }

  /**
   * @inheritDoc
   */
  public function click(Update $update): bool
  {
    $this->logger->debug(
      'Clicking settings option for the update ID={update_id}',
      [
        'update_id'   => $update->update_id,
        'update'      => $update,
      ]
    );

    if (is_null($telegramMessage = $this->getMessageFromUpdate($update))) {
      return false;
    }

    $this->comm->sendMessage(
      $telegramMessage->chat->id,
      'Blah',
      Communicator::PARSE_MODE_PLAIN
    );

    $this->logger->info(
      'Clicked settings option for the update ID={update_id}',
      [
        'update_id'   => $update->update_id,
        'update'      => $update,
      ]
    );

    return false;
  }

  /**
   * @inheritDoc
   */
  public function handler(Update $update, $parameter)
  {
    // TODO: Implement handler() method.
  }
}