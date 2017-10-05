<?php

namespace Kaula\TelegramBundle\DBAL\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\DateTimeType;

/**
 * UTCDateTimeType
 *
 * Based on the
 * http://docs.doctrine-project.org/en/2.0.x/cookbook/working-with-datetime.html,
 * but with some fixed bugs.
 *
 * @category   DBALType
 * @copyright  2013 Florian Eckerstorfer
 * @license    http://opensource.org/licenses/MIT The MIT License
 * @see        http://docs.doctrine-project.org/en/2.0.x/cookbook/working-with-datetime.html
 */
class UTCDateTimeType extends DateTimeType
{
  /**
   * {@inheritDoc}
   */
  public function convertToDatabaseValue($value, AbstractPlatform $platform)
  {
    if ($value === null) {
      return null;
    }

    if (!$value instanceof \DateTime) {
      return null;
    }

    $value->setTimezone(NowDateHelper::getUtc());

    return $value->format($platform->getDateTimeFormatString());
  }

  /**
   * {@inheritDoc}
   */
  public function convertToPHPValue($value, AbstractPlatform $platform)
  {
    if ($value === null) {
      return null;
    }

    $val = \DateTime::createFromFormat(
      $platform->getDateTimeFormatString(),
      $value,
      NowDateHelper::getUtc()
    );

    if (!$val) {
      throw ConversionException::conversionFailed($value, $this->getName());
    }

    return $val;
  }
}
