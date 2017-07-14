<?php
/**
 * Created by PhpStorm.
 * User: ant
 * Date: 25.04.2017
 * Time: 13:15
 */

namespace Kaula\TelegramBundle\Telegram\Event;


use RuntimeException;
use unreal4u\TelegramAPI\Telegram\Types\Update;

class ChatNewTitleEvent extends AbstractMessageEvent
{
  const NAME = 'telegram.chat.new_title';

  /**
   * ChatNewTitleEvent constructor.
   *
   * @param Update $update
   */
  public function __construct(Update $update)
  {
    if (is_null($update->message)) {
      throw new RuntimeException(
        'Message can\'t be null for the ChatNewTitleEvent.'
      );
    }
    if (empty($update->message->new_chat_title)) {
      throw new RuntimeException(
        'Chat title of the Message can\'t be empty for the ChatNewTitleEvent.'
      );
    }

    $this->setMessage($update->message)
      ->setUpdate($update);
  }

  /**
   * @return string
   */
  public function getChatTitle(): string
  {
    return $this->getMessage()->new_chat_title;
  }

}