<?php
/**
 * Created by PhpStorm.
 * User: ant
 * Date: 25.04.2017
 * Time: 13:15
 */

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
    if (is_null($update->message)) {
      throw new RuntimeException(
        'Message can\'t be null for the VenueReceivedEvent.'
      );
    }
    if (empty($update->message->venue)) {
      throw new RuntimeException(
        'Venue of the Message can\'t be empty for the VenueReceivedEvent.'
      );
    }

    $this->setMessage($update->message)
      ->setUpdate($update);
  }

  /**
   * @return Venue
   */
  public function getVenue(): Venue
  {
    return $this->getMessage()->venue;
  }

}