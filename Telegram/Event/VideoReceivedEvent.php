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
    if (is_null($update->message)) {
      throw new RuntimeException(
        'Message can\'t be null for the VideoReceivedEvent.'
      );
    }
    if (empty($update->message->video)) {
      throw new RuntimeException(
        'Video of the Message can\'t be empty for the VideoReceivedEvent.'
      );
    }

    $this->setMessage($update->message)
      ->setUpdate($update);
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