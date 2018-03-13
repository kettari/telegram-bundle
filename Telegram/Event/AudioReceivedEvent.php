<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram\Event;

use RuntimeException;
use unreal4u\TelegramAPI\Telegram\Types\Audio;
use unreal4u\TelegramAPI\Telegram\Types\Update;

class AudioReceivedEvent extends AbstractMessageEvent
{
  const NAME = 'telegram.audio.received';

  /**
   * AudioReceivedEvent constructor.
   *
   * @param Update $update
   */
  public function __construct(Update $update)
  {
    parent::__construct($update);
    if (empty($update->message->audio)) {
      throw new RuntimeException(
        'Audio of the Message can\'t be empty for the AudioReceivedEvent.'
      );
    }
  }

  /**
   * @return Audio
   */
  public function getAudio(): Audio
  {
    return $this->getMessage()->audio;
  }

}