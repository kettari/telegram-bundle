<?php
/**
 * Created by PhpStorm.
 * User: ant
 * Date: 26.04.2017
 * Time: 13:15
 */

namespace Kaula\TelegramBundle\Telegram\Event;


class RequestBlockedEvent extends AbstractMethodExceptionEvent
{
  const NAME = 'telegram.request.blocked';

  /**
   * @var string
   */
  private $chat_id;

  /**
   * RequestBlockedEvent constructor.
   *
   * @param string $chat_id
   * @param mixed $method
   * @param mixed $response
   */
  public function __construct($chat_id, $method, $response)
  {
    parent::__construct($method, $response);
    $this->chat_id = $chat_id;
  }

  /**
   * @return string
   */
  public function getChatId()
  {
    return $this->chat_id;
  }
}