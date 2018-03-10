<?php
/**
 * Created by PhpStorm.
 * User: ant
 * Date: 25.04.2017
 * Time: 13:15
 */

namespace Kettari\TelegramBundle\Telegram\Event;


use RuntimeException;
use unreal4u\TelegramAPI\Telegram\Types\Document;
use unreal4u\TelegramAPI\Telegram\Types\Update;

class DocumentReceivedEvent extends AbstractMessageEvent
{
  const NAME = 'telegram.document.received';

  /**
   * DocumentReceivedEvent constructor.
   *
   * @param Update $update
   */
  public function __construct(Update $update)
  {
    if (is_null($update->message)) {
      throw new RuntimeException(
        'Message can\'t be null for the DocumentReceivedEvent.'
      );
    }
    if (empty($update->message->document)) {
      throw new RuntimeException(
        'Document of the Message can\'t be empty for the DocumentReceivedEvent.'
      );
    }

    $this->setMessage($update->message)
      ->setUpdate($update);
  }

  /**
   * Message is a general file, information about the file
   *
   * @return Document
   */
  public function getDocument(): Document
  {
    return $this->getMessage()->document;
  }

  /**
   * Optional. Caption for the document, photo or video, 0-200 characters
   *
   * @return string
   */
  public function getCaption()
  {
    return $this->getMessage()->caption;
  }

}