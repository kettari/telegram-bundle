<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;

/**
 * @ORM\Entity
 * @ORM\Table(name="permission",indexes={@Index(name="permission_name_idx",columns={"name"})},
 *   options={"collate":"utf8mb4_general_ci", "charset":"utf8mb4"})
 */
class Permission {

  /**
   * @ORM\Column(type="integer")
   * @ORM\Id
   * @ORM\GeneratedValue(strategy="AUTO")
   */
  private $id;

  /**
   * @ORM\Column(type="string",length=50)
   *
   */
  private $name;

  /**
   * @ORM\ManyToMany(targetEntity="Kettari\TelegramBundle\Entity\Role",mappedBy="permissions")
   */
  private $roles;

  /**
   * Constructor
   */
  public function __construct() {
    $this->roles = new ArrayCollection();
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
   * @return Permission
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
   * Add role
   *
   * @param Role $role
   *
   * @return Permission
   */
  public function addRole(Role $role) {
    $this->roles[] = $role;

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
}
