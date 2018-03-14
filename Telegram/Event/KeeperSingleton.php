<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram\Event;


class KeeperSingleton
{
  /**
   * @var null
   */
  private static $instance = NULL;

  /**
   * @var bool
   */
  private $requestHandled = false;

  /**
   * @var array
   */
  private $storage = [];

  /**
   * @inheritDoc
   */
  private function __construct() {
  }

  /**
   * @inheritDoc
   */
  private function __clone() {
  }

  /**
   * Return instance of this singleton
   *
   * @return KeeperSingleton
   */
  public static function getInstance() {
    if (is_null(self::$instance)) {
      self::$instance = new KeeperSingleton();
    }

    return self::$instance;
  }

  /**
   * @return bool
   */
  public function isRequestHandled(): bool
  {
    return $this->requestHandled;
  }

  /**
   * @param bool $requestHandled
   * @return KeeperSingleton
   */
  public function setRequestHandled(bool $requestHandled): KeeperSingleton
  {
    $this->requestHandled = $requestHandled;

    return $this;
  }

  /**
   * @param string $key
   * @return mixed|null
   */
  public function getStorageItem($key)
  {
    return isset($this->storage[$key]) ? $this->storage[$key] : null;
  }

  /**
   * @param string $key
   * @param mixed $value
   * @return KeeperSingleton
   */
  public function setStorageItem($key, $value): KeeperSingleton
  {
    $this->storage[$key] = $value;

    return $this;
  }
}