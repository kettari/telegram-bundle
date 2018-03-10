<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;

/**
 * @ORM\Entity
 * @ORM\Table(name="chat_member_property",indexes={@Index(name="property_name_idx",
 *   columns={"property_name"})})
 */
class ChatMemberProperty
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
   * @ORM\ManyToOne(targetEntity="Kettari\TelegramBundle\Entity\ChatMember",inversedBy="properties")
   */
  private $chatMember;

  /**
   * @ORM\Column(type="string", length=100)
   */
  private $propertyName;

  /**
   * @ORM\Column(type="string")
   */
  private $propertyValue;

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
   * Get chat member
   *
   * @return \Kettari\TelegramBundle\Entity\ChatMember
   */
  public function getChatMember()
  {
    return $this->chatMember;
  }

  /**
   * Set user
   *
   * @param \Kettari\TelegramBundle\Entity\ChatMember|null $chatMember
   * @return \Kettari\TelegramBundle\Entity\ChatMemberProperty
   */
  public function setChatMember(ChatMember $chatMember = null)
  {
    $this->chatMember = $chatMember;

    return $this;
  }

  /**
   * @return string
   */
  public function getPropertyName()
  {
    return $this->propertyName;
  }

  /**
   * @param string $propertyName
   * @return \Kettari\TelegramBundle\Entity\ChatMemberProperty
   */
  public function setPropertyName($propertyName)
  {
    $this->propertyName = $propertyName;

    return $this;
  }

  /**
   * @return string
   */
  public function getPropertyValue()
  {
    return $this->propertyValue;
  }

  /**
   * @param string $propertyValue
   * @return ChatMemberProperty
   */
  public function setPropertyValue($propertyValue)
  {
    $this->propertyValue = $propertyValue;

    return $this;
  }
}