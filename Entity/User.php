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
 * @ORM\Entity(repositoryClass="Kaula\TelegramBundle\Repository\UserRepository")
 * @ORM\Table(name="user",indexes={@Index(name="tallanto_user_idx",columns={"tallanto_user_id","blocked"})},
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
  private $telegram_id;

  /**
   * @ORM\Column(type="string",length=255,nullable=true)
   *
   */
  private $first_name;

  /**
   * @ORM\Column(type="string",length=255,nullable=true)
   *
   */
  private $last_name;

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
   * @ORM\ManyToMany(targetEntity="Kaula\TelegramBundle\Entity\Role",inversedBy="users")
   */
  private $roles;

  /**
   * ~~OWNING SIDE~~
   *
   * Many Users have Many Notifications.
   *
   * @var Collection
   * @ORM\ManyToMany(targetEntity="Kaula\TelegramBundle\Entity\Notification",inversedBy="users")
   */
  private $notifications;

  /**
   * @ORM\Column(type="string",length=255,nullable=true)
   *
   */
  private $tallanto_contact_id;

  /**
   * @ORM\Column(type="string",length=255,nullable=true)
   *
   */
  private $tallanto_user_id;

  /**
   * @ORM\Column(type="boolean")
   *
   */
  private $blocked = false;

  /**
   * @ORM\Column(type="string",length=255,nullable=true)
   *
   */
  private $external_last_name;

  /**
   * @ORM\Column(type="string",length=255,nullable=true)
   *
   */
  private $external_first_name;

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
    $this->first_name = $firstName;

    return $this;
  }

  /**
   * Get firstName
   *
   * @return string
   */
  public function getFirstName()
  {
    return $this->first_name;
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
    $this->last_name = $lastName;

    return $this;
  }

  /**
   * Get lastName
   *
   * @return string
   */
  public function getLastName()
  {
    return $this->last_name;
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
   * @param \Kaula\TelegramBundle\Entity\Role|string $roleToCheck
   * @return bool
   */
  public function hasRole($roleToCheck)
  {
    if ($roleToCheck instanceof Role) {
      $checkName = $roleToCheck->getName();
    } else {
      $checkName = $roleToCheck;
    }

    /** @var \Kaula\TelegramBundle\Entity\Role $role */
    foreach ($this->getRoles() as $role) {
      if ($role->getName() == $checkName) {
        return true;
      }
    }

    return false;
  }

  /**
   * Set tallantoContactId
   *
   * @param string $tallantoContactId
   *
   * @return User
   */
  public function setTallantoContactId($tallantoContactId)
  {
    $this->tallanto_contact_id = $tallantoContactId;

    return $this;
  }

  /**
   * Get tallantoContactId
   *
   * @return string
   */
  public function getTallantoContactId()
  {
    return $this->tallanto_contact_id;
  }

  /**
   * Set tallantoUserId
   *
   * @param string $tallantoUserId
   *
   * @return User
   */
  public function setTallantoUserId($tallantoUserId)
  {
    $this->tallanto_user_id = $tallantoUserId;

    return $this;
  }

  /**
   * Get tallantoUserId
   *
   * @return string
   */
  public function getTallantoUserId()
  {
    return $this->tallanto_user_id;
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
    $this->telegram_id = $telegramId;

    return $this;
  }

  /**
   * Get telegramId
   *
   * @return integer
   */
  public function getTelegramId()
  {
    return $this->telegram_id;
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
    $this->external_last_name = $externalLastName;

    return $this;
  }

  /**
   * Get externalLastName
   *
   * @return string
   */
  public function getExternalLastName()
  {
    return $this->external_last_name;
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
    $this->external_first_name = $externalFirstName;

    return $this;
  }

  /**
   * Get externalFirstName
   *
   * @return string
   */
  public function getExternalFirstName()
  {
    return $this->external_first_name;
  }
}
