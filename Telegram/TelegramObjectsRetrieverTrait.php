<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram;


use unreal4u\TelegramAPI\Telegram\Types\Update;

trait TelegramObjectsRetrieverTrait
{
  /**
   * Tries to return correct User object.
   *
   * @param \unreal4u\TelegramAPI\Telegram\Types\Update $update
   * @return \unreal4u\TelegramAPI\Telegram\Types\User
   */
  protected function getUserFromUpdate(Update $update)
  {
    if (!is_null($message = $this->getMessageFromUpdate($update))) {
      return $message->from;
    } elseif (!is_null($update->edited_message)) {
      return $update->edited_message->from;
    } elseif (!is_null($update->channel_post)) {
      return $update->channel_post->from;
    }

    return null;
  }

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
    }

    return null;
  }

  /**
   * Tries to track down chat this update was sent from.
   *
   * @param Update $update
   * @return \unreal4u\TelegramAPI\Telegram\Types\Chat|null
   */
  protected function getChatFromUpdate(Update $update)
  {
    if (!is_null($message = $this->getMessageFromUpdate($update))) {
      return $message->chat;
    } elseif (!is_null($update->edited_message)) {
      return $update->edited_message->chat;
    } elseif (!is_null($update->channel_post)) {
      return $update->channel_post->chat;
    }

    return null;
  }
}