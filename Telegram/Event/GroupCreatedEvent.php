<?php
/**
 * Created by PhpStorm.
 * User: ant
 * Date: 25.04.2017
 * Time: 19:03
 */

namespace Kaula\TelegramBundle\Telegram\Event;


use RuntimeException;
use unreal4u\TelegramAPI\Telegram\Types\Update;

class GroupCreatedEvent extends AbstractMessageEvent
{
  const NAME = 'telegram.group.created';

  /**
   * GroupCreatedEvent constructor.
   *
   * @param Update $update
   */
  public function __construct(Update $update)
  {
    if (is_null($update->message)) {
      throw new RuntimeException(
        'Message can\'t be null for the GroupCreatedEvent.'
      );
    }

    $this->setMessage($update->message)
      ->setUpdate($update);
  }
}