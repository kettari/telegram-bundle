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
 * @ORM\Table(name="user",indexes={@Index(name="tallanto_idx",columns={"tallanto_contact_id","tallanto_user_id"})})
 */
class User {

  /**
   * @ORM\Column(type="bigint")
   * @ORM\Id
   * @ORM\GeneratedValue(strategy="NONE")
   */
  private $id;

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
   * Many Users have Many Roles.
   *
   * @var Collection
   * @ORM\ManyToMany(targetEntity="Kaula\TelegramBundle\Entity\Role",inversedBy="users")
   */
  private $roles;

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
   * Set id
   *
   * @param integer $id
   *
   * @return User
   */
  public function setId($id) {
    $this->id = $id;

    return $this;
  }

  /**
   * Get id
   *
   * @return integer
   */
  public function getId() {
    return $this->id;
  }

  /**
   * Set firstName
   *
   * @param string $firstName
   *
   * @return User
   */
  public function setFirstName($firstName) {
    $this->first_name = $firstName;

    return $this;
  }

  /**
   * Get firstName
   *
   * @return string
   */
  public function getFirstName() {
    return $this->first_name;
  }

  /**
   * Set lastName
   *
   * @param string $lastName
   *
   * @return User
   */
  public function setLastName($lastName) {
    $this->last_name = $lastName;

    return $this;
  }

  /**
   * Get lastName
   *
   * @return string
   */
  public function getLastName() {
    return $this->last_name;
  }

  /**
   * Set username
   *
   * @param string $username
   *
   * @return User
   */
  public function setUsername($username) {
    $this->username = $username;

    return $this;
  }

  /**
   * Get username
   *
   * @return string
   */
  public function getUsername() {
    return $this->username;
  }

  /**
   * Constructor
   */
  public function __construct() {
    $this->roles = new ArrayCollection();
  }

  /**
   * Set phone
   *
   * @param string $phone
   *
   * @return User
   */
  public function setPhone($phone) {
    $this->phone = $phone;

    return $this;
  }

  /**
   * Get phone
   *
   * @return string
   */
  public function getPhone() {
    return $this->phone;
  }

  /**
   * Add role
   *
   * @param Role $role
   *
   * @return User
   */
  public function addRole(Role $role) {
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
  public function removeRole(Role $role) {
    $this->roles->removeElement($role);
  }

  /**
   * Get roles
   *
   * @return \Doctrine\Common\Collections\Collection
   */
  public function getRoles() {
    return $this->roles;
  }


  /**
   * Set tallantoContactId
   *
   * @param string $tallantoContactId
   *
   * @return User
   */
  public function setTallantoContactId($tallantoContactId) {
    $this->tallanto_contact_id = $tallantoContactId;

    return $this;
  }

  /**
   * Get tallantoContactId
   *
   * @return string
   */
  public function getTallantoContactId() {
    return $this->tallanto_contact_id;
  }

  /**
   * Set tallantoUserId
   *
   * @param string $tallantoUserId
   *
   * @return User
   */
  public function setTallantoUserId($tallantoUserId) {
    $this->tallanto_user_id = $tallantoUserId;

    return $this;
  }

  /**
   * Get tallantoUserId
   *
   * @return string
   */
  public function getTallantoUserId() {
    return $this->tallanto_user_id;
  }
}
