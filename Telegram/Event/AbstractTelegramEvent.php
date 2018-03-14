<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram\Event;


use Symfony\Component\EventDispatcher\Event;

abstract class AbstractTelegramEvent extends Event
{
  /**
   * @var KeeperSingleton
   */
  private $keeper;

  /**
   * AbstractTelegramEvent constructor.
   */
  public function __construct()
  {
    $this->keeper = KeeperSingleton::getInstance();
  }

  /**
   * @return KeeperSingleton
   */
  public function getKeeper(): KeeperSingleton
  {
    return $this->keeper;
  }
}