<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="Kettari\TelegramBundle\Repository\BotSettingsRepository")
 * @ORM\Table(name="bot_setting")
 */
class BotSetting
{
  /**
   * @var string
   * @ORM\Column(type="string",nullable=false,unique=true)
   */
  private $name = '';

  /**
   * @var string
   * @ORM\Column(type="string",nullable=false)
   */
  private $value = '';

  /**
   * @return string
   */
  public function getName(): string
  {
    return $this->name;
  }

  /**
   * @param string $name
   * @return BotSetting
   */
  public function setName(string $name): BotSetting
  {
    $this->name = $name;

    return $this;
  }

  /**
   * @return string
   */
  public function getValue(): string
  {
    return $this->value;
  }

  /**
   * @param string $value
   * @return BotSetting
   */
  public function setValue(string $value): BotSetting
  {
    $this->value = $value;

    return $this;
  }
}