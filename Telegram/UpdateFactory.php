<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram;


use unreal4u\TelegramAPI\Abstracts\TelegramMethods;
use unreal4u\TelegramAPI\Telegram\Types\Update;

class UpdateFactory
{

  /**
   * Scrap incoming data into Update object.
   *
   * @return Update
   */
  public static function buildInput(): Update
  {
    $updateData = json_decode(file_get_contents('php://input'), true);
    if (JSON_ERROR_NONE != json_last_error()) {
      throw new \InvalidArgumentException('JSON error: '.json_last_error_msg());
    }

    return new Update($updateData);
  }

  /**
   * Builds array ready to be sent to Telegram server in the response.
   *
   * @param \unreal4u\TelegramAPI\Abstracts\TelegramMethods $method
   * @return array
   */
  public static function buildOutput(TelegramMethods $method): array
  {
    $completeClassName = get_class($method);
    $methodName = substr(
      $completeClassName,
      strrpos($completeClassName, '\\') + 1
    );

    return array_merge(['method' => $methodName], $method->export());
  }

}