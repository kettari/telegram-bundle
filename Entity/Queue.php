<?php
/**
 * Created by PhpStorm.
 * User: ant
 * Date: 18.03.2017
 * Time: 0:11
 */

namespace Kaula\TelegramBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;


/**
 * @ORM\Entity
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
   * @ORM\ManyToOne(targetEntity="Kaula\TelegramBundle\Entity\Chat")
   */
  private $chat;

  /**
   * @ORM\Column(type="text",nullable=true)
   */
  private $text;

  /**
   * @ORM\Column(type="string",nullable=true)
   */
  private $parse_mode;

  /**
   * @ORM\Column(type="string",nullable=true)
   */
  private $reply_markup;

  /**
   * @ORM\Column(type="boolean")
   */
  private $disable_web_page_preview;

  /**
   * @ORM\Column(type="boolean")
   */
  private $disable_notification;

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
     * Get status
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
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
     * Get created
     *
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->created;
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
     * Get sent
     *
     * @return \DateTime
     */
    public function getUpdated()
    {
        return $this->updated;
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
     * Get text
     *
     * @return string
     */
    public function getText()
    {
        return $this->text;
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
        $this->disable_web_page_preview = $disableWebPagePreview;

        return $this;
    }

    /**
     * Get disableWebPagePreview
     *
     * @return boolean
     */
    public function getDisableWebPagePreview()
    {
        return $this->disable_web_page_preview;
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
        $this->disable_notification = $disableNotification;

        return $this;
    }

    /**
     * Get disableNotification
     *
     * @return boolean
     */
    public function getDisableNotification()
    {
        return $this->disable_notification;
    }

    /**
     * Set chat
     *
     * @param \Kaula\TelegramBundle\Entity\Chat $chat
     *
     * @return Queue
     */
    public function setChat(Chat $chat = null)
    {
        $this->chat = $chat;

        return $this;
    }

    /**
     * Get chat
     *
     * @return \Kaula\TelegramBundle\Entity\Chat
     */
    public function getChat()
    {
        return $this->chat;
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
        $this->parse_mode = $parseMode;

        return $this;
    }

    /**
     * Get parseMode
     *
     * @return string
     */
    public function getParseMode()
    {
        return $this->parse_mode;
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
        $this->reply_markup = $replyMarkup;

        return $this;
    }

    /**
     * Get replyMarkup
     *
     * @return string
     */
    public function getReplyMarkup()
    {
        return $this->reply_markup;
    }
}
