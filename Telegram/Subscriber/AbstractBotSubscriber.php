<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram\Subscriber;


use Kettari\TelegramBundle\Telegram\CommunicatorInterface;
use Kettari\TelegramBundle\Telegram\UserHq;
use Kettari\TelegramBundle\Telegram\UserHqInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use unreal4u\TelegramAPI\Telegram\Types\Update;

abstract class AbstractBotSubscriber
{
  /**
   * Logger interface.
   *
   * @var LoggerInterface
   */
  protected $logger;

  /**
   * @var RegistryInterface
   */
  protected $doctrine;

  /**
   * @var EventDispatcherInterface
   */
  protected $dispatcher;

  /**
   * @var \Kettari\TelegramBundle\Telegram\UserHqInterface
   */
  protected $userHq;

  /**
   * @var CommunicatorInterface
   */
  protected $communicator;

  /**
   * AbstractBotSubscriber constructor.
   *
   * @param \Psr\Log\LoggerInterface $logger
   * @param \Symfony\Bridge\Doctrine\RegistryInterface $doctrine
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher
   * @param \Kettari\TelegramBundle\Telegram\UserHqInterface $userHq
   * @param \Kettari\TelegramBundle\Telegram\CommunicatorInterface $communicator
   */
  public function __construct(
    LoggerInterface $logger,
    RegistryInterface $doctrine,
    EventDispatcherInterface $dispatcher,
    UserHqInterface $userHq,
    CommunicatorInterface $communicator
  ) {
    $this->logger = $logger;
    $this->doctrine = $doctrine;
    $this->dispatcher = $dispatcher;
    $this->userHq = $userHq;
    $this->communicator = $communicator;
  }

  /**
   * Tries to track down chat this update was sent from.
   *
   * @param Update $update
   * @return \Kettari\TelegramBundle\Entity\Chat|null
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
      ->getRepository('KettariTelegramBundle:Chat')
      ->findOneBy(['telegram_id' => $chat_id]);
  }

  /**
   * Tries to track down user this update was sent from.
   *
   * @param Update $update
   * @return \Kettari\TelegramBundle\Entity\User|null
   */
  protected function resolveUser($update)
  {
    // Resolve user object
    $user_id = null;
    if (!is_null($update->message) && !is_null($update->message->from)) {
      $user_id = $update->message->from->id;
    } elseif (!is_null($update->edited_message) &&
      !is_null($update->edited_message->from)) {
      $user_id = $update->edited_message->from->id;
    } elseif (!is_null($update->channel_post) &&
      !is_null($update->channel_post->from)) {
      $user_id = $update->channel_post->from->id;
    } elseif (!is_null($update->callback_query)) {
      if (!is_null($update->callback_query->message) &&
        !is_null($update->callback_query->from)) {
        $user_id = $update->callback_query->from->id;
      }
    }

    return $this->getDoctrine()
      ->getRepository('KettariTelegramBundle:User')
      ->findOneBy(['telegram_id' => $user_id]);
  }

}