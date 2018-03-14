<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram\Event;


use Kettari\TelegramBundle\Telegram\TelegramObjectsRetrieverTrait;
use unreal4u\TelegramAPI\Telegram\Types\Message;
use unreal4u\TelegramAPI\Telegram\Types\Update;

abstract class AbstractMessageEvent extends AbstractUpdateEvent
{
  use TelegramObjectsRetrieverTrait;

  /**
   * @var Message
   */
  protected $message;

  /**
   * AbstractMessageEvent constructor.
   *
   * @param \unreal4u\TelegramAPI\Telegram\Types\Update $update
   */
  public function __construct(Update $update)
  {
    parent::__construct($update);
    $message = $this->getMessageFromUpdate($update);
    if (is_null($message)) {
      throw new \RuntimeException(
        'Message can\'t be null for the AbstractMessageEvent.'
      );
    }
    $this->message = $message;
  }

  /**
   * @return string
   */
  public function getText(): string
  {
    return $this->getMessage()->text ? $this->getMessage()->text : '';
  }

  /**
   * @return Message
   */
  public function getMessage(): Message
  {
    return $this->message;
  }

  /**
   * Returns true if Message has non-empty text.
   *
   * @return bool
   */
  public function hasText(): bool
  {
    return !empty($this->getMessage()->text);
  }
}