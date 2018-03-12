<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram;


use Kettari\TelegramBundle\Exception\TelegramBundleException;
use unreal4u\TelegramAPI\Telegram\Types\Update;

class UpdateTypeResolver
{
  // Update types
  const UT_MESSAGE = 'message';
  const UT_EDITED_MESSAGE = 'edited_message';
  const UT_CHANNEL_POST = 'channel_post';
  const UT_EDITED_CHANNEL_POST = 'edited_channel_post';
  const UT_INLINE_QUERY = 'inline_query';
  const UT_CHOSEN_INLINE_RESULT = 'chosen_inline_result';
  const UT_CALLBACK_QUERY = 'callback_query';

  /**
   * Returns type of the update.
   *
   * @param \unreal4u\TelegramAPI\Telegram\Types\Update $update
   * @return string
   * @throws TelegramBundleException
   */
  public static function getUpdateType(Update $update): string
  {
    if (!is_null($update->message)) {
      return self::UT_MESSAGE;
    }
    if (!is_null($update->edited_message)) {
      return self::UT_EDITED_MESSAGE;
    }
    if (!is_null($update->channel_post)) {
      return self::UT_CHANNEL_POST;
    }
    if (!is_null($update->edited_channel_post)) {
      return self::UT_EDITED_CHANNEL_POST;
    }
    if (!is_null($update->inline_query)) {
      return self::UT_INLINE_QUERY;
    }
    if (!is_null($update->chosen_inline_result)) {
      return self::UT_CHOSEN_INLINE_RESULT;
    }
    if (!is_null($update->callback_query)) {
      return self::UT_CALLBACK_QUERY;
    }

    throw new TelegramBundleException('Unknown update type.');
  }
}