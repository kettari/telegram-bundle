<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram\Event;


use RuntimeException;
use unreal4u\TelegramAPI\Telegram\Types\Update;
use unreal4u\TelegramAPI\Telegram\Types\Video;

class VideoReceivedEvent extends AbstractMessageEvent
{
  const NAME = 'telegram.video.received';

  /**
   * VideoReceivedEvent constructor.
   *
   * @param Update $update
   */
  public function __construct(Update $update)
  {
    parent::__construct($update);
    if (empty($update->message->video)) {
      throw new RuntimeException(
        'Video of the Message can\'t be empty for the VideoReceivedEvent.'
      );
    }
  }

  /**
   * Message is a general file, information about the file
   *
   * @return Video
   */
  public function getVideo(): Video
  {
    return $this->getMessage()->video;
  }

  /**
   * Optional. Caption for the document, photo or video, 0-200 characters
   *
   * @return string
   */
  public function getCaption()
  {
    return $this->getMessage()->caption;
  }

}