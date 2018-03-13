<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;


/**
 * @ORM\Entity(repositoryClass="Kettari\TelegramBundle\Repository\QueueRepository")
 * @ORM\Table(name="queue",indexes={@Index(name="status_idx",columns={"status"}),
 *   @Index(name="created_idx",columns={"created"})},
 *   options={"collate":"utf8mb4_general_ci", "charset":"utf8mb4"})
 */
class Queue
{

  /**
   * @ORM\Column(type="integer")
   * @ORM\Id
   * @ORM\GeneratedValue(strategy="AUTO")
   */
  private $id;

  /**
   * @ORM\Column(type="string",length=20)
   */
  private $status;

  /**
   * @ORM\Column(type="datetime")
   */
  private $created;

  /**
   * @ORM\Column(type="datetime",nullable=true)
   */
  private $updated;

  /**
   * @ORM\ManyToOne(targetEntity="Kettari\TelegramBundle\Entity\Chat")
   */
  private $chat;

  /**
   * @ORM\Column(type="text",nullable=true)
   */
  private $text;

  /**
   * @ORM\Column(type="string",nullable=true)
   */
  private $parseMode;

  /**
   * @ORM\Column(type="text",nullable=true)
   */
  private $replyMarkup;

  /**
   * @ORM\Column(type="boolean")
   */
  private $disableWebPagePreview;

  /**
   * @ORM\Column(type="boolean")
   */
  private $disableNotification;

  /**
   * @ORM\Column(type="text",nullable=true)
   */
  private $exceptionMessage;

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
   * Get status
   *
   * @return string
   */
  public function getStatus()
  {
    return $this->status;
  }

  /**
   * Set status
   *
   * @param string $status
   *
   * @return Queue
   */
  public function setStatus($status)
  {
    $this->status = $status;

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
   * Set created
   *
   * @param \DateTime $created
   *
   * @return Queue
   */
  public function setCreated($created)
  {
    $this->created = $created;

    return $this;
  }

  /**
   * Get sent
   *
   * @return \DateTime
   */
  public function getUpdated()
  {
    return $this->updated;
  }

  /**
   * Set sent
   *
   * @param \DateTime $updated
   *
   * @return Queue
   */
  public function setUpdated($updated)
  {
    $this->updated = $updated;

    return $this;
  }

  /**
   * Get text
   *
   * @return string
   */
  public function getText()
  {
    return $this->text;
  }

  /**
   * Set text
   *
   * @param string $text
   *
   * @return Queue
   */
  public function setText($text)
  {
    $this->text = $text;

    return $this;
  }

  /**
   * Get disableWebPagePreview
   *
   * @return boolean
   */
  public function getDisableWebPagePreview()
  {
    return $this->disableWebPagePreview;
  }

  /**
   * Set disableWebPagePreview
   *
   * @param boolean $disableWebPagePreview
   *
   * @return Queue
   */
  public function setDisableWebPagePreview($disableWebPagePreview)
  {
    $this->disableWebPagePreview = $disableWebPagePreview;

    return $this;
  }

  /**
   * Get disableNotification
   *
   * @return boolean
   */
  public function getDisableNotification()
  {
    return $this->disableNotification;
  }

  /**
   * Set disableNotification
   *
   * @param boolean $disableNotification
   *
   * @return Queue
   */
  public function setDisableNotification($disableNotification)
  {
    $this->disableNotification = $disableNotification;

    return $this;
  }

  /**
   * Get chat
   *
   * @return \Kettari\TelegramBundle\Entity\Chat
   */
  public function getChat()
  {
    return $this->chat;
  }

  /**
   * Set chat
   *
   * @param \Kettari\TelegramBundle\Entity\Chat $chat
   *
   * @return Queue
   */
  public function setChat(Chat $chat = null)
  {
    $this->chat = $chat;

    return $this;
  }

  /**
   * Get parseMode
   *
   * @return string
   */
  public function getParseMode()
  {
    return $this->parseMode;
  }

  /**
   * Set parseMode
   *
   * @param string $parseMode
   *
   * @return Queue
   */
  public function setParseMode($parseMode)
  {
    $this->parseMode = $parseMode;

    return $this;
  }

  /**
   * Get replyMarkup
   *
   * @return string
   */
  public function getReplyMarkup()
  {
    return $this->replyMarkup;
  }

  /**
   * Set replyMarkup
   *
   * @param string $replyMarkup
   *
   * @return Queue
   */
  public function setReplyMarkup($replyMarkup)
  {
    $this->replyMarkup = $replyMarkup;

    return $this;
  }

  /**
   * Get exceptionMessage
   *
   * @return string
   */
  public function getExceptionMessage()
  {
    return $this->exceptionMessage;
  }

  /**
   * Set exceptionMessage
   *
   * @param string $exceptionMessage
   *
   * @return Queue
   */
  public function setExceptionMessage($exceptionMessage)
  {
    $this->exceptionMessage = $exceptionMessage;

    return $this;
  }
}
