<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="Kettari\TelegramBundle\Repository\HookRepository")
 * @ORM\Table(name="hook",
 *   options={"collate":"utf8mb4_general_ci", "charset":"utf8mb4"})
 */
class Hook
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
   * @ORM\ManyToOne(targetEntity="Kettari\TelegramBundle\Entity\Chat")
   */
  private $chat;

  /**
   * @ORM\ManyToOne(targetEntity="Kettari\TelegramBundle\Entity\User")
   */
  private $user;

  /**
   * @ORM\Column(type="string",length=255)
   *
   */
  private $serviceId;

  /**
   * @ORM\Column(type="string",length=255)
   *
   */
  private $methodName;

  /**
   * @ORM\Column(type="text",nullable=true)
   *
   */
  private $parameter;


  /**
   * Get id
   *
   * @return integer
   */
  public function getId(): int
  {
    return $this->id;
  }

  /**
   * Set created
   *
   * @param \DateTime $created
   *
   * @return Hook
   */
  public function setCreated($created): Hook
  {
    $this->created = $created;

    return $this;
  }

  /**
   * Get created
   *
   * @return \DateTime
   */
  public function getCreated(): \DateTime
  {
    return $this->created;
  }

  /**
   * Set className
   *
   * @param string $serviceId
   *
   * @return Hook
   */
  public function setServiceId($serviceId): Hook
  {
    $this->serviceId = $serviceId;

    return $this;
  }

  /**
   * Get className
   *
   * @return string
   */
  public function getServiceId(): string
  {
    return $this->serviceId;
  }

  /**
   * Set methodName
   *
   * @param string $methodName
   *
   * @return Hook
   */
  public function setMethodName($methodName): Hook
  {
    $this->methodName = $methodName;

    return $this;
  }

  /**
   * Get methodName
   *
   * @return string
   */
  public function getMethodName(): string
  {
    return $this->methodName;
  }

  /**
   * Set parameters
   *
   * @param string $parameter
   *
   * @return Hook
   */
  public function setParameter($parameter): Hook
  {
    $this->parameter = $parameter;

    return $this;
  }

  /**
   * Get parameters
   *
   * @return string
   */
  public function getParameter()
  {
    return $this->parameter;
  }

  /**
   * Set chat
   *
   * @param Chat $chat
   *
   * @return Hook
   */
  public function setChat(Chat $chat): Hook
  {
    $this->chat = $chat;

    return $this;
  }

  /**
   * Get chat
   *
   * @return Chat
   */
  public function getChat(): Chat
  {
    return $this->chat;
  }

  /**
   * Set user
   *
   * @param User $user
   *
   * @return Hook
   */
  public function setUser(User $user): Hook
  {
    $this->user = $user;

    return $this;
  }

  /**
   * Get user
   *
   * @return User
   */
  public function getUser(): User
  {
    return $this->user;
  }
}
