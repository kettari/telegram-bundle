<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram\Event;

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
    parent::__construct($update);
    if (empty($update->message->new_chat_title)) {
      throw new RuntimeException(
        'Chat title of the Message can\'t be empty for the ChatNewTitleEvent.'
      );
    }
  }

  /**
   * @return string
   */
  public function getChatTitle(): string
  {
    return $this->getMessage()->new_chat_title;
  }

}