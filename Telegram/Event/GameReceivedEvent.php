<?php
/**
 * Created by PhpStorm.
 * User: ant
 * Date: 25.04.2017
 * Time: 13:15
 */

namespace Kaula\TelegramBundle\Telegram\Event;


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
    if (is_null($update->message)) {
      throw new RuntimeException(
        'Message can\'t be null for the GameReceivedEvent.'
      );
    }
    if (empty($update->message->game)) {
      throw new RuntimeException(
        'Game of the Message can\'t be empty for the GameReceivedEvent.'
      );
    }

    $this->setMessage($update->message)
      ->setUpdate($update);
  }

  /**
   * @return Game
   */
  public function getGame(): Game
  {
    return $this->getMessage()->game;
  }

}