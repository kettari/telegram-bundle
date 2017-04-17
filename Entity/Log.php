<?php
/**
 * Created by PhpStorm.
 * User: ant
 * Date: 17.04.2017
 * Time: 15:00
 */

namespace Kaula\TelegramBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="log")
 */
class Log
{

  /**
   * @ORM\Column(type="integer")
   * @ORM\Id
   * @ORM\GeneratedValue(strategy="AUTO")
   */
  private $id;

  /**
   * @ORM\Column(type="datetime")
   *
   */
  private $created;

  /**
   * @ORM\Column(type="string",length=10)
   */
  private $direction;

  /**
   * @ORM\Column(type="string",length=255,nullable=true)
   */
  private $type;

  /**
   * @ORM\Column(type="bigint",nullable=true)
   */
  private $telegram_chat_id;

  /**
   * @ORM\Column(type="text",nullable=true)
   */
  private $content;

  /**
   * Get id
   *
   * @return integer
   */
  public function getId()
  {
    return $this->id;
  }

  /**
   * Set created
   *
   * @param \DateTime $created
   *
   * @return Log
   */
  public function setCreated($created)
  {
    $this->created = $created;

    return $this;
  }

  /**
   * Get created
   *
   * @return \DateTime
   */
  public function getCreated()
  {
    return $this->created;
  }

  /**
   * Set direction
   *
   * @param string $direction
   *
   * @return Log
   */
  public function setDirection($direction)
  {
    $this->direction = $direction;

    return $this;
  }

  /**
   * Get direction
   *
   * @return string
   */
  public function getDirection()
  {
    return $this->direction;
  }

  /**
   * Set updateType
   *
   * @param string $updateType
   *
   * @return Log
   */
  public function setType($updateType)
  {
    $this->type = $updateType;

    return $this;
  }

  /**
   * Get updateType
   *
   * @return string
   */
  public function getType()
  {
    return $this->type;
  }

  /**
   * Set telegramChatId
   *
   * @param integer $telegramChatId
   *
   * @return Log
   */
  public function setTelegramChatId($telegramChatId)
  {
    $this->telegram_chat_id = $telegramChatId;

    return $this;
  }

  /**
   * Get telegramChatId
   *
   * @return integer
   */
  public function getTelegramChatId()
  {
    return $this->telegram_chat_id;
  }

  /**
   * Set content
   *
   * @param string $content
   *
   * @return Log
   */
  public function setContent($content)
  {
    $this->content = $content;

    return $this;
  }

  /**
   * Get content
   *
   * @return string
   */
  public function getContent()
  {
    return $this->content;
  }
}
