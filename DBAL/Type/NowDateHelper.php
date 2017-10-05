<?php
/**
 * Created by PhpStorm.
 * User: ant
 * Date: 13.09.2017
 * Time: 20:10
 */

namespace Kaula\TelegramBundle\DBAL\Type;


class NowDateHelper
{
  /** @var \DateTimeZone */
  static private $utc = null;

  /**
   * @return \DateTimeZone
   */
  public static function getUtc()
  {
    return self::$utc ? self::$utc : self::$utc = new \DateTimeZone('UTC');
  }

  /**
   * @return \DateTime
   */
  public static function getNow() {
    return new \DateTime('now', self::getUtc());
  }
}