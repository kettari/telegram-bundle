<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram;


use unreal4u\TelegramAPI\Telegram\Types\Update;

trait TelegramObjectsRetrieverTrait
{
  /**
   * Tries to return correct Message object.
   *
   * @param \unreal4u\TelegramAPI\Telegram\Types\Update $update
   * @return \unreal4u\TelegramAPI\Telegram\Types\Message
   */
  protected function getMessageFromUpdate(Update $update)
  {
    if (!is_null($update->message)) {
      return $update->message;
    } elseif (!is_null($update->callback_query) &&
      (!is_null($update->callback_query->message))) {
      return $update->callback_query->message;
    } else {
      return null;
    }
  }

  /**
   * Tries to return correct User object.
   *
   * @param \unreal4u\TelegramAPI\Telegram\Types\Update $update
   * @return \unreal4u\TelegramAPI\Telegram\Types\User
   */
  protected function getUserFromUpdate(Update $update)
  {
    if (!is_null($update->callback_query)) {
      return $update->callback_query->from;
    } elseif (!is_null($m = $this->getMessageFromUpdate($update))) {
      return $m->from;
    } else {
      return null;
    }
  }
}