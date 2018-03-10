<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;


/**
 * @ORM\Entity(repositoryClass="Kettari\TelegramBundle\Repository\ChatRepository")
 * @ORM\Table(name="chat",
 *   options={"collate":"utf8mb4_general_ci", "charset":"utf8mb4"})
 */
class Chat
{

  /**
   * @ORM\Column(type="integer")
   * @ORM\Id
   * @ORM\GeneratedValue(strategy="AUTO")
   */
  private $id;

  /**
   * @ORM\Column(type="bigint",unique=true)
   */
  private $telegramId;

  /**
   * @ORM\Column(type="string",length=255)
   *
   */
  private $type;

  /**
   * @ORM\Column(type="string",length=255)
   *
   */
  private $title;

  /**
   * @ORM\Column(type="string",length=255,nullable=true)
   *
   */
  private $username;

  /**
   * @ORM\Column(type="string",length=255,nullable=true)
   *
   */
  private $firstName;

  /**
   * @ORM\Column(type="string",length=255,nullable=true)
   *
   */
  private $lastName;

  /**
   * @ORM\Column(type="boolean")
   *
   */
  private $allMembersAreAdministrators;

  /**
   * ~~INVERSE SIDE~~
   *
   * @ORM\OneToMany(targetEntity="Kettari\TelegramBundle\Entity\ChatMember",mappedBy="chat")
   */
  private $chatMembers;

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
   * Get chatType
   *
   * @return string
   */
  public function getType()
  {
    return $this->type;
  }

  /**
   * Set chatType
   *
   * @param string $chatType
   *
   * @return Chat
   */
  public function setType($chatType)
  {
    $this->type = $chatType;

    return $this;
  }

  /**
   * Get title
   *
   * @return string
   */
  public function getTitle()
  {
    return $this->title;
  }

  /**
   * Set title
   *
   * @param string $title
   *
   * @return Chat
   */
  public function setTitle($title)
  {
    $this->title = $title;

    return $this;
  }

  /**
   * Get username
   *
   * @return string
   */
  public function getUsername()
  {
    return $this->username;
  }

  /**
   * Set username
   *
   * @param string $username
   *
   * @return Chat
   */
  public function setUsername($username)
  {
    $this->username = $username;

    return $this;
  }

  /**
   * Get firstName
   *
   * @return string
   */
  public function getFirstName()
  {
    return $this->firstName;
  }

  /**
   * Set firstName
   *
   * @param string $firstName
   *
   * @return Chat
   */
  public function setFirstName($firstName)
  {
    $this->firstName = $firstName;

    return $this;
  }

  /**
   * Get lastName
   *
   * @return string
   */
  public function getLastName()
  {
    return $this->lastName;
  }

  /**
   * Set lastName
   *
   * @param string $lastName
   *
   * @return Chat
   */
  public function setLastName($lastName)
  {
    $this->lastName = $lastName;

    return $this;
  }

  /**
   * Get allMembersAreAdministrators
   *
   * @return boolean
   */
  public function getAllMembersAreAdministrators()
  {
    return $this->allMembersAreAdministrators;
  }

  /**
   * Set allMembersAreAdministrators
   *
   * @param boolean $allMembersAreAdministrators
   *
   * @return Chat
   */
  public function setAllMembersAreAdministrators($allMembersAreAdministrators)
  {
    $this->allMembersAreAdministrators = $allMembersAreAdministrators;

    return $this;
  }

  /**
   * Get telegramId
   *
   * @return integer
   */
  public function getTelegramId()
  {
    return $this->telegramId;
  }

  /**
   * Set telegramId
   *
   * @param integer $telegramId
   *
   * @return Chat
   */
  public function setTelegramId($telegramId)
  {
    $this->telegramId = $telegramId;

    return $this;
  }
  /**
   * Constructor
   */
  public function __construct()
  {
    $this->chatMembers = new ArrayCollection();
  }

  /**
   * Add chatMember
   *
   * @param ChatMember $chatMember
   *
   * @return Chat
   */
  public function addChatMember(ChatMember $chatMember)
  {
    $this->chatMembers[] = $chatMember;

    return $this;
  }

  /**
   * Remove chatMember
   *
   * @param ChatMember $chatMember
   */
  public function removeChatMember(ChatMember $chatMember)
  {
    $this->chatMembers->removeElement($chatMember);
  }

  /**
   * Get chatMembers
   *
   * @return \Doctrine\Common\Collections\Collection
   */
  public function getChatMembers()
  {
    return $this->chatMembers;
  }
}
