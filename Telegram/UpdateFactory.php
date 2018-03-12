<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram;


use unreal4u\TelegramAPI\Telegram\Types\Update;

class UpdateFactory
{

  /**
   * Scrap incoming data into Update object.
   *
   * @return Update
   */
  public static function build(): Update
  {
    $updateData = json_decode(file_get_contents('php://input'), true);
    if (JSON_ERROR_NONE != json_last_error()) {
      throw new \InvalidArgumentException('JSON error: '.json_last_error_msg());
    }

    return new Update($updateData);
  }

}