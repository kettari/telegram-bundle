<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram\Event;


use RuntimeException;
use unreal4u\TelegramAPI\Telegram\Types\Game;
use unreal4u\TelegramAPI\Telegram\Types\Update;

class GameReceivedEvent extends AbstractMessageEvent
{
  const NAME = 'telegram.game.received';

  /**
   * GameReceivedEvent constructor.
   *
   * @param Update $update
   */
  public function __construct(Update $update)
  {
    parent::__construct($update);
    if (empty($update->message->game)) {
      throw new RuntimeException(
        'Game of the Message can\'t be empty for the GameReceivedEvent.'
      );
    }
  }

  /**
   * @return Game
   */
  public function getGame(): Game
  {
    return $this->getMessage()->game;
  }

}