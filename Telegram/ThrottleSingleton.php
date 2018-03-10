<?php
/**
 * Created by PhpStorm.
 * User: ant
 * Date: 13.03.2017
 * Time: 14:14
 */

namespace Kettari\TelegramBundle\Telegram;

/**
 * Class ThrottleSingleton
 *
 * @package AmoCrm\Client
 */
class ThrottleSingleton
{

  /**
   * Idle time in seconds between checks while waiting for cooldown
   */
  const IDLE_SECONDS = 0.02;

  /**
   * Average number of requests allowed per second within WINDOW_SPAN
   */
  const REQUESTS_PER_SECOND = 20;

  /**
   * Time window to analyze requests
   */
  const WINDOW_SPAN = 10;

  /**
   * Number of requests allowed within burst with length 1 second
   */
  const BURST_COUNT = 30;

  /**
   * Cooldown period in seconds
   */
  const COOLDOWN_SPAN = 60;

  /**
   * @var null
   */
  private static $instance = null;

  /**
   * @var array
   */
  protected $requests = [];

  /**
   * @var int
   */
  protected $idle_time = 0;

  /**
   * @var int
   */
  protected $queue_length_peak = 0;

  /**
   * @inheritDoc
   */
  private function __construct()
  {
  }

  /**
   * Return instance of this singleton
   *
   * @return ThrottleSingleton
   */
  public static function getInstance()
  {
    if (is_null(self::$instance)) {
      self::$instance = new ThrottleSingleton();
    }

    return self::$instance;
  }

  /**
   * When API request is sent this method must be called
   */
  public function requestSent()
  {
    $this->requests[] = microtime(true);

    // Collect statistics
    if (count($this->requests) > $this->queue_length_peak) {
      $this->queue_length_peak = count($this->requests);
    }
  }

  /**
   * Wait until request is allowed
   *
   * @return bool
   */
  public function wait()
  {
    $wait_start = microtime(true);
    while (!$this->isRequestAllowed()) {
      $now = microtime(true);
      if (($now - $wait_start) > self::COOLDOWN_SPAN) {
        // Relax time exceeded, request still not allowed
        return false;
      }
      usleep(self::IDLE_SECONDS * 1000000);

      // Collect statistics
      $this->idle_time += self::IDLE_SECONDS;
    }

    return true;
  }

  /**
   * Is request allowed?
   *
   * @return bool
   */
  public function isRequestAllowed()
  {
    $this->cleanQueue();

    // Average vote
    $average_exceeded = ((count($this->requests) / self::WINDOW_SPAN) >
      self::REQUESTS_PER_SECOND);
    if ($average_exceeded) {
      return false;
    }

    // If there was no burst, allow request immediately
    if (!$this->wasBurst()) {
      return true;
    }

    // Normal speed
    if ($this->requestsLastSecond() >= self::REQUESTS_PER_SECOND) {
      return false;
    }

    return true;
  }

  /**
   * Cleans the queue, removes old requests.
   */
  private function cleanQueue()
  {
    $now = microtime(true);
    $cutoff = $now - self::WINDOW_SPAN;
    foreach ($this->requests as $key => $rq) {
      // If request is older than WINDOW_SPAN, remove it from the queue
      if ($rq < $cutoff) {
        unset($this->requests[$key]);
      }
    }
  }

  /**
   * Checks if there was a burst in the queue.
   *
   * @return bool
   */
  private function wasBurst()
  {
    $burst_queue = [];
    foreach ($this->requests as $key => $rq) {
      $index = (int)floor($rq);
      if (!isset($burst_queue[$index])) {
        $burst_queue[$index] = 1;
      } else {
        $burst_queue[$index]++;
      }
      // If we have burst, vote to disallow request
      if ($burst_queue[$index] >= self::BURST_COUNT) {
        return true;
      }
    }

    return false;
  }

  /**
   * Counts requests in the last second.
   *
   * @return int
   */
  private function requestsLastSecond()
  {
    $now = microtime(true);
    $cutoff = $now - 1;
    $last_sec_queue = $this->requests;
    foreach ($last_sec_queue as $key => $rq) {
      // If request is older than 1 second, remove it from the queue
      if ($rq < $cutoff) {
        unset($last_sec_queue[$key]);
      }
    }

    return count($last_sec_queue);
  }

  /**
   * Returns count of waiting cycles.
   *
   * @return int
   */
  public function getIdleTime()
  {
    return $this->idle_time;
  }

  /**
   * Returns queue length peak.
   *
   * @return int
   */
  public function getQueueLengthPeak()
  {
    return $this->queue_length_peak;
  }

  /**
   * @inheritDoc
   */
  private function __clone()
  {
  }

}