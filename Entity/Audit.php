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
 * @ORM\Table(name="audit")
 */
class Audit
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
   * @ORM\Column(type="string",length=255,nullable=true)
   */
  private $type;

  /**
   * @ORM\Column(type="text",nullable=true)
   */
  private $description;

  /**
   * @ORM\ManyToOne(targetEntity="Kaula\TelegramBundle\Entity\Chat")
   */
  private $chat;

  /**
   * @ORM\ManyToOne(targetEntity="Kaula\TelegramBundle\Entity\User")
   */
  private $user;

  /**
   * @ORM\Column(type="text",nullable=true)
   */
  private $content;


  /**
   * Audit constructor.
   */
  public function __construct()
  {
    $this->created = new \DateTime('now', new \DateTimeZone('UTC'));
  }

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
   * Get created
   *
   * @return \DateTime
   */
  public function getCreated()
  {
    return $this->created;
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
   * Set updateType
   *
   * @param string $updateType
   *
   * @return Audit
   */
  public function setType($updateType)
  {
    $this->type = $updateType;

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

  /**
   * Set content
   *
   * @param string $content
   *
   * @return Audit
   */
  public function setContent($content)
  {
    $this->content = $content;

    return $this;
  }

  /**
   * Get description
   *
   * @return string
   */
  public function getDescription()
  {
    return $this->description;
  }

  /**
   * Set description
   *
   * @param string $description
   *
   * @return Audit
   */
  public function setDescription($description)
  {
    $this->description = $description;

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
   * Set chat
   *
   * @param \Kaula\TelegramBundle\Entity\Chat $chat
   *
   * @return Audit
   */
  public function setChat(Chat $chat = null)
  {
    $this->chat = $chat;

    return $this;
  }

  /**
   * Get user
   *
   * @return \Kaula\TelegramBundle\Entity\User
   */
  public function getUser()
  {
    return $this->user;
  }

  /**
   * Set user
   *
   * @param \Kaula\TelegramBundle\Entity\User $user
   *
   * @return Audit
   */
  public function setUser(User $user = null)
  {
    $this->user = $user;

    return $this;
  }
}
