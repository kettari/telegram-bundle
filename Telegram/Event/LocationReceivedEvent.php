<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram\Event;


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
    parent::__construct($update);
    if (empty($update->message->location)) {
      throw new RuntimeException(
        'Location of the Message can\'t be empty for the LocationReceivedEvent.'
      );
    }
  }

  /**
   * @return Location
   */
  public function getLocation(): Location
  {
    return $this->getMessage()->location;
  }

}