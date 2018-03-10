<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="Kettari\TelegramBundle\Repository\UserRepository")
 * @ORM\Table(name="user",indexes={)},
 *   options={"collate":"utf8mb4_general_ci", "charset":"utf8mb4"})
 */
class User
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
   * @ORM\Column(type="string",length=255,nullable=true)
   *
   */
  private $username;

  /**
   * @ORM\Column(type="string",length=255,nullable=true)
   *
   */
  private $phone;

  /**
   * ~~OWNING SIDE~~
   *
   * Many Users have Many Roles.
   *
   * @var Collection
   * @ORM\ManyToMany(targetEntity="Kettari\TelegramBundle\Entity\Role",inversedBy="users")
   */
  private $roles;

  /**
   * ~~OWNING SIDE~~
   *
   * Many Users have Many Notifications.
   *
   * @var Collection
   * @ORM\ManyToMany(targetEntity="Kettari\TelegramBundle\Entity\Notification",inversedBy="users")
   */
  private $notifications;

  /**
   * @ORM\Column(type="boolean")
   *
   */
  private $blocked = false;

  /**
   * @ORM\Column(type="string",length=255,nullable=true)
   *
   */
  private $externalLastName;

  /**
   * @ORM\Column(type="string",length=255,nullable=true)
   *
   */
  private $externalFirstName;

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
   * Set firstName
   *
   * @param string $firstName
   *
   * @return User
   */
  public function setFirstName($firstName)
  {
    $this->firstName = $firstName;

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
   * Set lastName
   *
   * @param string $lastName
   *
   * @return User
   */
  public function setLastName($lastName)
  {
    $this->lastName = $lastName;

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
   * Set username
   *
   * @param string $username
   *
   * @return User
   */
  public function setUsername($username)
  {
    $this->username = $username;

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
   * Constructor
   */
  public function __construct()
  {
    $this->roles = new ArrayCollection();
  }

  /**
   * Set phone
   *
   * @param string $phone
   *
   * @return User
   */
  public function setPhone($phone)
  {
    $this->phone = $phone;

    return $this;
  }

  /**
   * Get phone
   *
   * @return string
   */
  public function getPhone()
  {
    return $this->phone;
  }

  /**
   * Add role
   *
   * @param Role $role
   *
   * @return User
   */
  public function addRole(Role $role)
  {
    if (!$this->getRoles()
      ->contains($role)
    ) {
      $this->roles[] = $role;
    }

    return $this;
  }

  /**
   * Remove role
   *
   * @param Role $role
   */
  public function removeRole(Role $role)
  {
    $this->roles->removeElement($role);
  }

  /**
   * Get roles
   *
   * @return \Doctrine\Common\Collections\Collection
   */
  public function getRoles()
  {
    return $this->roles;
  }

  /**
   * Check if user has specified role.
   *
   * @param \Kettari\TelegramBundle\Entity\Role|string $roleToCheck
   * @return bool
   */
  public function hasRole($roleToCheck)
  {
    if ($roleToCheck instanceof Role) {
      $checkName = $roleToCheck->getName();
    } else {
      $checkName = $roleToCheck;
    }

    /** @var \Kettari\TelegramBundle\Entity\Role $role */
    foreach ($this->getRoles() as $role) {
      if ($role->getName() == $checkName) {
        return true;
      }
    }

    return false;
  }

  /**
   * Set telegramId
   *
   * @param integer $telegramId
   *
   * @return User
   */
  public function setTelegramId($telegramId)
  {
    $this->telegramId = $telegramId;

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
   * Add notification
   *
   * @param Notification $notification
   *
   * @return User
   */
  public function addNotification(Notification $notification)
  {
    $this->notifications[] = $notification;

    return $this;
  }

  /**
   * Remove notification
   *
   * @param Notification $notification
   */
  public function removeNotification(
    Notification $notification
  ) {
    $this->notifications->removeElement($notification);
  }

  /**
   * Get notifications
   *
   * @return \Doctrine\Common\Collections\Collection
   */
  public function getNotifications()
  {
    return $this->notifications;
  }

  /**
   * Set blocked
   *
   * @param boolean $blocked
   *
   * @return User
   */
  public function setBlocked($blocked)
  {
    $this->blocked = $blocked;

    return $this;
  }

  /**
   * Is blocked
   *
   * @return boolean
   */
  public function isBlocked()
  {
    return $this->blocked;
  }

  /**
   * Set externalLastName
   *
   * @param string $externalLastName
   *
   * @return User
   */
  public function setExternalLastName($externalLastName)
  {
    $this->externalLastName = $externalLastName;

    return $this;
  }

  /**
   * Get externalLastName
   *
   * @return string
   */
  public function getExternalLastName()
  {
    return $this->externalLastName;
  }

  /**
   * Set externalFirstName
   *
   * @param string $externalFirstName
   *
   * @return User
   */
  public function setExternalFirstName($externalFirstName)
  {
    $this->externalFirstName = $externalFirstName;

    return $this;
  }

  /**
   * Get externalFirstName
   *
   * @return string
   */
  public function getExternalFirstName()
  {
    return $this->externalFirstName;
  }
}
