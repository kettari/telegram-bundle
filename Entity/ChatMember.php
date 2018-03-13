<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;

/**
 * @ORM\Entity(repositoryClass="Kettari\TelegramBundle\Repository\ChatMemberRepository")
 * @ORM\Table(name="chat_member",indexes={@Index(name="chat_user_idx", columns={"chat_id","user_id"})},
 *   options={"collate":"utf8mb4_general_ci", "charset":"utf8mb4"})
 */
class ChatMember
{

  /**
   * @ORM\Column(type="integer")
   * @ORM\Id
   * @ORM\GeneratedValue(strategy="AUTO")
   */
  private $id;

  /**
   * ~~OWNING SIDE~~
   *
   * @ORM\ManyToOne(targetEntity="Kettari\TelegramBundle\Entity\User")
   */
  private $user;

  /**
   * ~~OWNING SIDE~~
   *
   * @ORM\ManyToOne(targetEntity="Kettari\TelegramBundle\Entity\Chat",inversedBy="chatMembers")
   *
   */
  private $chat;

  /**
   * @ORM\Column(type="string",length=20)
   */
  private $status;

  /**
   * @ORM\Column(type="datetime",nullable=true)
   */
  private $joinDate;

  /**
   * @ORM\Column(type="datetime",nullable=true)
   */
  private $leaveDate;

  /**
   * ~~INVERSE SIDE~~
   *
   * @var Collection
   * @ORM\OneToMany(targetEntity="Kettari\TelegramBundle\Entity\ChatMemberProperty",mappedBy="chatMember")
   */
  private $properties;

  /**
   * ChatMember constructor.
   */
  public function __construct()
  {
    $this->properties = new ArrayCollection();
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
   * @return ChatMember
   */
  public function setStatus($status)
  {
    $this->status = $status;

    return $this;
  }

  /**
   * Get joinDate
   *
   * @return \DateTime
   */
  public function getJoinDate()
  {
    return $this->joinDate;
  }

  /**
   * Set joinDate
   *
   * @param \DateTime $joinDate
   *
   * @return ChatMember
   */
  public function setJoinDate($joinDate)
  {
    $this->joinDate = $joinDate;

    return $this;
  }

  /**
   * Get leaveDate
   *
   * @return \DateTime
   */
  public function getLeaveDate()
  {
    return $this->leaveDate;
  }

  /**
   * Set leaveDate
   *
   * @param \DateTime $leaveDate
   *
   * @return ChatMember
   */
  public function setLeaveDate($leaveDate)
  {
    $this->leaveDate = $leaveDate;

    return $this;
  }

  /**
   * Get user
   *
   * @return User
   */
  public function getUser()
  {
    return $this->user;
  }

  /**
   * Set user
   *
   * @param User $user
   *
   * @return ChatMember
   */
  public function setUser(User $user = null)
  {
    $this->user = $user;

    return $this;
  }

  /**
   * Get chat
   *
   * @return Chat
   */
  public function getChat()
  {
    return $this->chat;
  }

  /**
   * Set chat
   *
   * @param Chat $chat
   *
   * @return ChatMember
   */
  public function setChat(Chat $chat = null)
  {
    $this->chat = $chat;

    return $this;
  }

  /**
   * Add property
   *
   * @param \Kettari\TelegramBundle\Entity\ChatMemberProperty $chatMemberProperty
   *
   * @return \Kettari\TelegramBundle\Entity\ChatMember
   */
  public function addProperty(ChatMemberProperty $chatMemberProperty)
  {
    if (!$this->getProperties()
      ->contains($chatMemberProperty)) {
      $this->properties[] = $chatMemberProperty;
    }

    return $this;
  }

  /**
   * Get properties
   *
   * @return \Doctrine\Common\Collections\Collection
   */
  public function getProperties()
  {
    return $this->properties;
  }

  /**
   * Remove property
   *
   * @param \Kettari\TelegramBundle\Entity\ChatMemberProperty $chatMemberProperty
   */
  public function removeProperty(ChatMemberProperty $chatMemberProperty)
  {
    $this->properties->removeElement($chatMemberProperty);
  }
}
