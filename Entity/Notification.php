<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;


/**
 * @ORM\Entity
 * @ORM\Table(name="notification",indexes={@Index(name="order_idx",columns={"sort_order"}),
 *   @Index(name="default_idx",columns={"user_default"})},
 *   options={"collate":"utf8mb4_general_ci", "charset":"utf8mb4"})
 */
class Notification
{

  /**
   * @ORM\Column(type="integer")
   * @ORM\Id
   * @ORM\GeneratedValue(strategy="AUTO")
   */
  private $id;

  /**
   * @ORM\Column(type="integer",nullable=true)
   */
  private $sortOrder;

  /**
   * @ORM\Column(type="string",length=50,unique=true)
   *
   */
  private $name;

  /**
   * @ORM\Column(type="string",length=255)
   *
   */
  private $title;

  /**
   * @ORM\Column(type="boolean")
   */
  private $userDefault = false;

  /**
   * @ORM\ManyToOne(targetEntity="Kettari\TelegramBundle\Entity\Permission")
   */
  private $permission;

  /**
   * Many Notifications have Many Users.
   *
   * @var Collection
   * @ORM\ManyToMany(targetEntity="Kettari\TelegramBundle\Entity\User",mappedBy="notifications")
   */
  private $users;

  /**
   * Constructor
   */
  public function __construct()
  {
    $this->users = new ArrayCollection();
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
   * Set order
   *
   * @param integer $sortOrder
   *
   * @return Notification
   */
  public function setSortOrder($sortOrder)
  {
    $this->sortOrder = $sortOrder;

    return $this;
  }

  /**
   * Get order
   *
   * @return integer
   */
  public function getSortOrder()
  {
    return $this->sortOrder;
  }

  /**
   * Set name
   *
   * @param string $name
   *
   * @return Notification
   */
  public function setName($name)
  {
    $this->name = $name;

    return $this;
  }

  /**
   * Get name
   *
   * @return string
   */
  public function getName()
  {
    return $this->name;
  }

  /**
   * Set title
   *
   * @param string $title
   *
   * @return Notification
   */
  public function setTitle($title)
  {
    $this->title = $title;

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
   * Set default
   *
   * @param boolean $userDefault
   *
   * @return Notification
   */
  public function setUserDefault($userDefault)
  {
    $this->userDefault = $userDefault;

    return $this;
  }

  /**
   * Get default
   *
   * @return boolean
   */
  public function getUserDefault()
  {
    return $this->userDefault;
  }

  /**
   * Add user
   *
   * @param \Kettari\TelegramBundle\Entity\User $user
   *
   * @return Notification
   */
  public function addUser(User $user)
  {
    $this->users[] = $user;

    return $this;
  }

  /**
   * Remove user
   *
   * @param \Kettari\TelegramBundle\Entity\User $user
   */
  public function removeUser(User $user)
  {
    $this->users->removeElement($user);
  }

  /**
   * Get users
   *
   * @return \Doctrine\Common\Collections\Collection
   */
  public function getUsers()
  {
    return $this->users;
  }

  /**
   * Set permission
   *
   * @param \Kettari\TelegramBundle\Entity\Permission $permission
   *
   * @return Notification
   */
  public function setPermission(
    Permission $permission = null
  ) {
    $this->permission = $permission;

    return $this;
  }

  /**
   * Get permission
   *
   * @return \Kettari\TelegramBundle\Entity\Permission
   */
  public function getPermission()
  {
    return $this->permission;
  }
}
