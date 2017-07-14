<?php
/**
 * Created by PhpStorm.
 * User: ant
 * Date: 25.04.2017
 * Time: 14:46
 */

namespace Kaula\TelegramBundle\Telegram\Subscriber;


use Doctrine\Bundle\DoctrineBundle\Registry;
use Kaula\TelegramBundle\Telegram\Bot;
use unreal4u\TelegramAPI\Telegram\Types\Update;

abstract class AbstractBotSubscriber
{
  /**
   * @var Bot
   */
  private $bot;

  /**
   * AbstractBotSubscriber constructor.
   *
   * @param Bot $bot
   */
  public function __construct(Bot $bot)
  {
    $this->bot = $bot;
  }

  /**
   * Tries to track down chat this update was sent from.
   *
   * @param Update $update
   * @return \Kaula\TelegramBundle\Entity\Chat|null
   */
  protected function resolveChat($update)
  {
    // Resolve chat object
    $chat_id = null;
    if (!is_null($update->message)) {
      $chat_id = $update->message->chat->id;
    } elseif (!is_null($update->edited_message)) {
      $chat_id = $update->edited_message->chat->id;
    } elseif (!is_null($update->channel_post)) {
      $chat_id = $update->channel_post->chat->id;
    } elseif (!is_null($update->callback_query)) {
      if (!is_null($update->callback_query->message)) {
        $chat_id = $update->callback_query->message->chat->id;
      }
    }

    return $this->getDoctrine()
      ->getRepository('KaulaTelegramBundle:Chat')
      ->findOneBy(['telegram_id' => $chat_id]);
  }

  /**
   * Tries to track down user this update was sent from.
   *
   * @param Update $update
   * @return \Kaula\TelegramBundle\Entity\User|null
   */
  protected function resolveUser($update) {
    // Resolve user object
    $user_id = null;
    if (!is_null($update->message) && !is_null($update->message->from)) {
      $user_id = $update->message->from->id;
    } elseif (!is_null($update->edited_message) &&
      !is_null($update->edited_message->from)
    ) {
      $user_id = $update->edited_message->from->id;
    } elseif (!is_null($update->channel_post) &&
      !is_null($update->channel_post->from)
    ) {
      $user_id = $update->channel_post->from->id;
    } elseif (!is_null($update->callback_query)) {
      if (!is_null($update->callback_query->message) &&
        !is_null($update->callback_query->from)
      ) {
        $user_id = $update->callback_query->from->id;
      }
    }
    return $this->getDoctrine()
      ->getRepository('KaulaTelegramBundle:User')
      ->findOneBy(['telegram_id' => $user_id]);
  }

  /**
   * @return Registry
   */
  public function getDoctrine(): Registry
  {
    return $this->getBot()
      ->getDoctrine();
  }

  /**
   * @return Bot
   */
  public function getBot(): Bot
  {
    return $this->bot;
  }

}