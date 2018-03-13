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
  private $className;

  /**
   * @ORM\Column(type="string",length=255)
   *
   */
  private $methodName;

  /**
   * @ORM\Column(type="text",nullable=true)
   *
   */
  private $parameters;


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
   * @return Hook
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
   * Set className
   *
   * @param string $className
   *
   * @return Hook
   */
  public function setClassName($className)
  {
    $this->className = $className;

    return $this;
  }

  /**
   * Get className
   *
   * @return string
   */
  public function getClassName()
  {
    return $this->className;
  }

  /**
   * Set methodName
   *
   * @param string $methodName
   *
   * @return Hook
   */
  public function setMethodName($methodName)
  {
    $this->methodName = $methodName;

    return $this;
  }

  /**
   * Get methodName
   *
   * @return string
   */
  public function getMethodName()
  {
    return $this->methodName;
  }

  /**
   * Set parameters
   *
   * @param string $parameters
   *
   * @return Hook
   */
  public function setParameters($parameters)
  {
    $this->parameters = $parameters;

    return $this;
  }

  /**
   * Get parameters
   *
   * @return string
   */
  public function getParameters()
  {
    return $this->parameters;
  }

  /**
   * Set chat
   *
   * @param Chat $chat
   *
   * @return Hook
   */
  public function setChat(Chat $chat = null)
  {
    $this->chat = $chat;

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
   * Set user
   *
   * @param User $user
   *
   * @return Hook
   */
  public function setUser(User $user = null)
  {
    $this->user = $user;

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
}
