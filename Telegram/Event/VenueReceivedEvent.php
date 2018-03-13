<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram\Event;

use RuntimeException;
use unreal4u\TelegramAPI\Telegram\Types\Update;
use unreal4u\TelegramAPI\Telegram\Types\Venue;

class VenueReceivedEvent extends AbstractMessageEvent
{
  const NAME = 'telegram.venue.received';

  /**
   * VenueReceivedEvent constructor.
   *
   * @param Update $update
   */
  public function __construct(Update $update)
  {
    parent::__construct($update);
    if (empty($update->message->venue)) {
      throw new RuntimeException(
        'Venue of the Message can\'t be empty for the VenueReceivedEvent.'
      );
    }
  }

  /**
   * @return Venue
   */
  public function getVenue(): Venue
  {
    return $this->getMessage()->venue;
  }

}