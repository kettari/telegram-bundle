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
use unreal4u\TelegramAPI\Telegram\Types\VideoNote;

class VideoNoteReceivedEvent extends AbstractMessageEvent
{
  const NAME = 'telegram.video_note.received';

  /**
   * VideoNoteReceivedEvent constructor.
   *
   * @param Update $update
   */
  public function __construct(Update $update)
  {
    if (is_null($update->message)) {
      throw new RuntimeException(
        'Message can\'t be null for the VideoNoteReceivedEvent.'
      );
    }
    if (empty($update->message->video_note)) {
      throw new RuntimeException(
        'Video note of the Message can\'t be empty for the VideoNoteReceivedEvent.'
      );
    }

    $this->setMessage($update->message)
      ->setUpdate($update);
  }

  /**
   * Message is a video note.
   *
   * @return VideoNote
   */
  public function getVideoNote(): VideoNote
  {
    return $this->getMessage()->video_note;
  }

}