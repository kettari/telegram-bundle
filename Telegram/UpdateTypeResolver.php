<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram;


use Kettari\TelegramBundle\Exception\TelegramBundleException;
use unreal4u\TelegramAPI\Telegram\Types\Update;

class UpdateTypeResolver
{
  // Update types
  const UT_MESSAGE = 'UT_MESSAGE';
  const UT_EDITED_MESSAGE = 'UT_EDITED_MESSAGE';
  const UT_CHANNEL_POST = 'UT_CHANNEL_POST';
  const UT_EDITED_CHANNEL_POST = 'UT_EDITED_CHANNEL_POST';
  const UT_INLINE_QUERY = 'UT_INLINE_QUERY';
  const UT_CHOSEN_INLINE_RESULT = 'UT_CHOSEN_INLINE_RESULT';
  const UT_CALLBACK_QUERY = 'UT_CALLBACK_QUERY';

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