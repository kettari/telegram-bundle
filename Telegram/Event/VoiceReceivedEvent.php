<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram\Event;


use RuntimeException;
use unreal4u\TelegramAPI\Telegram\Types\Update;
use unreal4u\TelegramAPI\Telegram\Types\Voice;

class VoiceReceivedEvent extends AbstractMessageEvent
{
  const NAME = 'telegram.voice.received';

  /**
   * VoiceReceivedEvent constructor.
   *
   * @param Update $update
   */
  public function __construct(Update $update)
  {
    parent::__construct($update);
    if (empty($update->message->voice)) {
      throw new RuntimeException(
        'Voice of the Message can\'t be empty for the VoiceReceivedEvent.'
      );
    }
  }

  /**
   * Message is a voice message, information about the file
   *
   * @return Voice
   */
  public function getVoice(): Voice
  {
    return $this->getMessage()->voice;
  }

}