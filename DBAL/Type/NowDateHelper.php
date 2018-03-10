<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\DBAL\Type;


class NowDateHelper
{
  /** @var \DateTimeZone */
  static private $utc = null;

  /**
   * @return \DateTime
   */
  public static function getNow(): \DateTime
  {
    return new \DateTime('now', self::getUtc());
  }

  /**
   * @return \DateTimeZone
   */
  public static function getUtc(): \DateTimeZone
  {
    return self::$utc ? self::$utc : self::$utc = new \DateTimeZone('UTC');
  }
}