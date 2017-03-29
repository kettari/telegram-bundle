<?php
/**
 * Created by PhpStorm.
 * User: ant
 * Date: 18.03.2017
 * Time: 0:11
 */

namespace Kaula\TelegramBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;


/**
 * @ORM\Entity
 * @ORM\Table(name="notification",indexes={@Index(name="order_idx",columns={"order"}),
 *   @Index(name="default_idx",columns={"default"}),
 *   @Index(name="name_idx",columns={"name"})})
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
  private $order;

  /**
   * @ORM\Column(type="string",length=50)
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
  private $default = false;

  /**
   * @ORM\ManyToOne(targetEntity="Kaula\TelegramBundle\Entity\Permission")
   */
  private $permission;

  /**
   * Many Notifications have Many Users.
   *
   * @var Collection
   * @ORM\ManyToMany(targetEntity="Kaula\TelegramBundle\Entity\User",mappedBy="notifications")
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
   * @param integer $order
   *
   * @return Notification
   */
  public function setOrder($order)
  {
    $this->order = $order;

    return $this;
  }

  /**
   * Get order
   *
   * @return integer
   */
  public function getOrder()
  {
    return $this->order;
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
   * @param boolean $default
   *
   * @return Notification
   */
  public function setDefault($default)
  {
    $this->default = $default;

    return $this;
  }

  /**
   * Get default
   *
   * @return boolean
   */
  public function getDefault()
  {
    return $this->default;
  }

  /**
   * Add user
   *
   * @param \Kaula\TelegramBundle\Entity\User $user
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
   * @param \Kaula\TelegramBundle\Entity\User $user
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
   * @param \Kaula\TelegramBundle\Entity\Permission $permission
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
   * @return \Kaula\TelegramBundle\Entity\Permission
   */
  public function getPermission()
  {
    return $this->permission;
  }
}
