<?php
/**
 * Created by PhpStorm.
 * User: ant
 * Date: 25.04.2017
 * Time: 19:03
 */

namespace Kettari\TelegramBundle\Telegram\Event;


use RuntimeException;
use unreal4u\TelegramAPI\Telegram\Types\Update;

class LeftChatMemberEvent extends AbstractMessageEvent
{
  const NAME = 'telegram.chatmember.left';

  /**
   * LeftChatMemberEvent constructor.
   *
   * @param Update $update
   */
  public function __construct(Update $update)
  {
    if (is_null($update->message)) {
      throw new RuntimeException(
        'Message can\'t be null for the LeftChatMemberEvent.'
      );
    }

    $this->setMessage($update->message)
      ->setUpdate($update);
  }
}