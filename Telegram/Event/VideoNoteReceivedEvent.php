<?php
declare(strict_types=1);

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
    parent::__construct($update);
    if (empty($update->message->video_note)) {
      throw new RuntimeException(
        'Video note of the Message can\'t be empty for the VideoNoteReceivedEvent.'
      );
    }
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