<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram\Event;


use unreal4u\TelegramAPI\Abstracts\TelegramMethods;

class RequestBlockedEvent extends AbstractMethodExceptionEvent
{
  const NAME = 'telegram.request.blocked';

  /**
   * @var string
   */
  private $chatId = null;

  /**
   * RequestBlockedEvent constructor.
   *
   * @param TelegramMethods $method
   * @param null|string $chatId
   * @param mixed $response
   */
  public function __construct(TelegramMethods $method, $chatId, $response)
  {
    parent::__construct($method, $response);
    $this->chatId = $chatId;
  }

  /**
   * @return null|string
   */
  public function getChatId()
  {
    return $this->chatId;
  }
}