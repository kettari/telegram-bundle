<?php
/**
 * Created by PhpStorm.
 * User: ant
 * Date: 25.04.2017
 * Time: 13:15
 */

namespace Kaula\TelegramBundle\Telegram\Event;


use RuntimeException;
use unreal4u\TelegramAPI\Telegram\Types\Location;
use unreal4u\TelegramAPI\Telegram\Types\Update;

class LocationReceivedEvent extends AbstractMessageEvent
{
  const NAME = 'telegram.location.received';

  /**
   * LocationReceivedEvent constructor.
   *
   * @param Update $update
   */
  public function __construct(Update $update)
  {
    if (is_null($update->message)) {
      throw new RuntimeException(
        'Message can\'t be null for the LocationReceivedEvent.'
      );
    }
    if (empty($update->message->location)) {
      throw new RuntimeException(
        'Location of the Message can\'t be empty for the LocationReceivedEvent.'
      );
    }

    $this->setMessage($update->message)
      ->setUpdate($update);
  }

  /**
   * @return Location
   */
  public function getLocation(): Location
  {
    return $this->getMessage()->location;
  }

}