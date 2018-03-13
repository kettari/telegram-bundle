<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram\Event;


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
    parent::__construct($update);
    if (empty($update->message->contact)) {
      throw new RuntimeException(
        'Contact of the Message can\'t be empty for the ContactReceivedEvent.'
      );
    }
  }

  /**
   * @return Contact
   */
  public function getContact(): Contact
  {
    return $this->getMessage()->contact;
  }

}