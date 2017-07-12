<?php
/**
 * Created by PhpStorm.
 * User: ant
 * Date: 25.04.2017
 * Time: 13:15
 */

namespace Kaula\TelegramBundle\Telegram\Event;


use RuntimeException;
use unreal4u\TelegramAPI\Telegram\Types\Contact;
use unreal4u\TelegramAPI\Telegram\Types\Update;

class ContactReceivedEvent extends AbstractMessageEvent
{
  const NAME = 'telegram.contact.received';

  /**
   * ContactReceivedEvent constructor.
   *
   * @param Update $update
   */
  public function __construct(Update $update)
  {
    if (is_null($update->message)) {
      throw new RuntimeException(
        'Message can\'t be null for the ContactReceivedEvent.'
      );
    }
    if (empty($update->message->contact)) {
      throw new RuntimeException(
        'Contact of the Message can\'t be empty for the ContactReceivedEvent.'
      );
    }

    $this->setMessage($update->message)
      ->setUpdate($update);
  }

  /**
   * @return Contact
   */
  public function getContact(): Contact
  {
    return $this->getMessage()->contact;
  }

}