<?php
/**
 * Created by PhpStorm.
 * User: ant
 * Date: 25.04.2017
 * Time: 13:15
 */

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
    if (is_null($update->message)) {
      throw new RuntimeException(
        'Message can\'t be null for the AudioReceivedEvent.'
      );
    }
    if (empty($update->message->audio)) {
      throw new RuntimeException(
        'Audio of the Message can\'t be empty for the AudioReceivedEvent.'
      );
    }

    $this->setMessage($update->message)
      ->setUpdate($update);
  }

  /**
   * @return Audio
   */
  public function getAudio(): Audio
  {
    return $this->getMessage()->audio;
  }

}