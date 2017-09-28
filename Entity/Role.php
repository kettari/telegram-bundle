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
 * @ORM\Table(name="role",indexes={@Index(name="permissions_anonymous_idx",columns={"anonymous"}),
 *   @Index(name="permissions_administrator_idx",columns={"administrator"})},
 *   options={"collate":"utf8mb4_general_ci", "charset":"utf8mb4"})
 */
class Role {

  /**
   * @ORM\Column(type="integer")
   * @ORM\Id
   * @ORM\GeneratedValue(strategy="AUTO")
   */
  private $id;

  /**
   * @ORM\Column(type="string",length=50,unique=true)
   *
   */
  private $name;

  /**
   * @var Collection
   * @ORM\ManyToMany(targetEntity="Kaula\TelegramBundle\Entity\Permission",inversedBy="roles")
   */
  private $permissions;

  /**
   * @ORM\Column(type="boolean")
   *
   */
  private $anonymous = false;

  /**
   * @ORM\Column(type="boolean")
   *
   */
  private $administrator = false;

  /**
   * Many Roles have Many Users.
   *
   * @ORM\ManyToMany(targetEntity="Kaula\TelegramBundle\Entity\User",mappedBy="roles")
   */
  private $users;

  /**
   * Constructor
   */
  public function __construct() {
    $this->users = new ArrayCollection();
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
   * Set name
   *
   * @param string $name
   *
   * @return Role
   */
  public function setName($name) {
    $this->name = $name;

    return $this;
  }

  /**
   * Get name
   *
   * @return string
   */
  public function getName() {
    return $this->name;
  }

  /**
   * Set anonymous
   *
   * @param boolean $anonymous
   *
   * @return Role
   */
  public function setAnonymous($anonymous) {
    $this->anonymous = $anonymous;

    return $this;
  }

  /**
   * Get anonymous
   *
   * @return boolean
   */
  public function getAnonymous() {
    return $this->anonymous;
  }

  /**
   * Set administrator
   *
   * @param boolean $administrator
   *
   * @return Role
   */
  public function setAdministrator($administrator) {
    $this->administrator = $administrator;

    return $this;
  }

  /**
   * Get administrator
   *
   * @return boolean
   */
  public function getAdministrator() {
    return $this->administrator;
  }

  /**
   * Add user
   *
   * @param User $user
   *
   * @return Role
   */
  public function addUser(User $user) {
    if (!$this->getUsers()
      ->contains($user)
    ) {
      $this->users[] = $user;
    }

    return $this;
  }

  /**
   * Remove user
   *
   * @param User $user
   */
  public function removeUser(User $user) {
    $this->users->removeElement($user);
  }

  /**
   * Get users
   *
   * @return \Doctrine\Common\Collections\Collection
   */
  public function getUsers() {
    return $this->users;
  }

  /**
   * Add permission
   *
   * @param Permission $permission
   *
   * @return Role
   */
  public function addPermission(Permission $permission) {
    $this->permissions[] = $permission;

    return $this;
  }

  /**
   * Remove permission
   *
   * @param Permission $permission
   */
  public function removePermission(Permission $permission) {
    $this->permissions->removeElement($permission);
  }

  /**
   * Get permissions
   *
   * @return \Doctrine\Common\Collections\Collection
   */
  public function getPermissions() {
    return $this->permissions;
  }
}
