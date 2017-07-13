<?php
/**
 * Created by PhpStorm.
 * User: ant
 * Date: 18.03.2017
 * Time: 0:11
 */

namespace Kaula\TelegramBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;

/**
 * @ORM\Entity
 * @ORM\Table(name="chat_member",indexes={@Index(name="chat_user_idx", columns={"chat_id","user_id"})},
 *   options={"collate":"utf8mb4_general_ci", "charset":"utf8mb4"})
 */
class ChatMember {

  /**
   * @ORM\Column(type="integer")
   * @ORM\Id
   * @ORM\GeneratedValue(strategy="AUTO")
   */
  private $id;

  /**
   * @ORM\ManyToOne(targetEntity="Kaula\TelegramBundle\Entity\User")
   *
   */
  private $user;

  /**
   * @ORM\ManyToOne(targetEntity="Kaula\TelegramBundle\Entity\Chat")
   *
   */
  private $chat;

  /**
   * @ORM\Column(type="string",length=20)
   */
  private $status;

  /**
   * @ORM\Column(type="datetime",nullable=true)
   */
  private $join_date;

  /**
   * @ORM\Column(type="datetime",nullable=true)
   */
  private $leave_date;


  /**
   * Get id
   *
   * @return integer
   */
  public function getId() {
    return $this->id;
  }

  /**
   * Set status
   *
   * @param string $status
   *
   * @return ChatMember
   */
  public function setStatus($status) {
    $this->status = $status;

    return $this;
  }

  /**
   * Get status
   *
   * @return string
   */
  public function getStatus() {
    return $this->status;
  }

  /**
   * Set joinDate
   *
   * @param \DateTime $joinDate
   *
   * @return ChatMember
   */
  public function setJoinDate($joinDate) {
    $this->join_date = $joinDate;

    return $this;
  }

  /**
   * Get joinDate
   *
   * @return \DateTime
   */
  public function getJoinDate() {
    return $this->join_date;
  }

  /**
   * Set leaveDate
   *
   * @param \DateTime $leaveDate
   *
   * @return ChatMember
   */
  public function setLeaveDate($leaveDate) {
    $this->leave_date = $leaveDate;

    return $this;
  }

  /**
   * Get leaveDate
   *
   * @return \DateTime
   */
  public function getLeaveDate() {
    return $this->leave_date;
  }

  /**
   * Set user
   *
   * @param User $user
   *
   * @return ChatMember
   */
  public function setUser(User $user = NULL) {
    $this->user = $user;

    return $this;
  }

  /**
   * Get user
   *
   * @return User
   */
  public function getUser() {
    return $this->user;
  }

  /**
   * Set chat
   *
   * @param Chat $chat
   *
   * @return ChatMember
   */
  public function setChat(Chat $chat = NULL) {
    $this->chat = $chat;

    return $this;
  }

  /**
   * Get chat
   *
   * @return Chat
   */
  public function getChat() {
    return $this->chat;
  }
}
