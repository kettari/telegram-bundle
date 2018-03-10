<?php
/**
 * Created by PhpStorm.
 * User: ant
 * Date: 25.04.2017
 * Time: 13:15
 */

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
    if (is_null($update->message)) {
      throw new RuntimeException(
        'Message can\'t be null for the VoiceReceivedEvent.'
      );
    }
    if (empty($update->message->voice)) {
      throw new RuntimeException(
        'Voice of the Message can\'t be empty for the VoiceReceivedEvent.'
      );
    }

    $this->setMessage($update->message)
      ->setUpdate($update);
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