<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram;


use Kettari\TelegramBundle\Entity\Queue;
use Kettari\TelegramBundle\Entity\User;
use Kettari\TelegramBundle\Exception\TelegramBundleException;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Stopwatch\Stopwatch;

class Pusher implements PusherInterface
{
  /**
   * Logger interface.
   *
   * @var LoggerInterface
   */
  private $logger;

  /**
   * @var RegistryInterface
   */
  private $doctrine;

  /**
   * @var \Kettari\TelegramBundle\Telegram\CommunicatorInterface
   */
  private $communicator;

  /**
   * Pusher constructor.
   *
   * @param LoggerInterface $logger
   * @param RegistryInterface $doctrine
   * @param \Kettari\TelegramBundle\Telegram\CommunicatorInterface $communicator
   */
  public function __construct(
    LoggerInterface $logger,
    RegistryInterface $doctrine,
    CommunicatorInterface $communicator
  ) {
    $this->logger = $logger;
    $this->doctrine = $doctrine;
    $this->communicator = $communicator;
  }

  /**
   * {@inheritdoc}
   */
  public function pushNotification(
    string $notification,
    string $text,
    string $parseMode = Communicator::PARSE_MODE_PLAIN,
    $replyMarkup = null,
    $disableWebPagePreview = false,
    $disableNotification = false,
    User $recipient = null,
    $chatId = null
  ) {
    $this->logger->info(
      'Pushing notification "{notification}" for telegram user={telegram_user_id} in the chat ID={chat_id}',
      [
        'notification'     => $notification,
        'telegram_user_id' => !is_null($recipient) ? $recipient->getTelegramId(
        ) : '(all-subscribers)',
        'chat_id'          => is_null($chatId) ? '(chat not defined)' : $chatId,
      ]
    );

    $subscribers = $this->getEligibleSubscribers($notification, $recipient);
    $this->logger->info(
      'Subscribers to receive notification: {subscribers_count}',
      ['subscribers_count' => count($subscribers)]
    );

    $now = new \DateTime('now', new \DateTimeZone('UTC'));
    /** @var \Kettari\TelegramBundle\Entity\User $userItem */
    foreach ($subscribers as $userItem) {
      if ($userItem->isBlocked()) {
        continue;
      }

      if (is_null(
        $chat = $this->doctrine->getRepository('KettariTelegramBundle:Chat')
          ->findOneByTelegramId(
            is_null($chatId) ? $userItem->getTelegramId() : $chatId
          )
      )) {
        throw new TelegramBundleException(
          sprintf(
            'Queue for push failed: unable to find chat for user ID=%s',
            $userItem->getTelegramId()
          )
        );
      }

      $queue = new Queue();
      $queue->setStatus('pending')
        ->setCreated($now)
        ->setChat($chat)
        ->setText($text)
        ->setParseMode($parseMode)
        ->setReplyMarkup(
          !is_null($replyMarkup) ? serialize($replyMarkup) : null
        )
        ->setDisableWebPagePreview($disableWebPagePreview)
        ->setDisableNotification($disableNotification);
      $this->doctrine->getManager()
        ->persist($queue);
    }
    $this->doctrine->getManager()
      ->flush();
  }

  /**
   * Returns array of subscribers who will receive notification.
   *
   * @param string $notification Name of the notification
   * @param User|null $recipient Recipient who intended to receive notification
   * @return array|\Doctrine\Common\Collections\Collection
   */
  private function getEligibleSubscribers(string $notification, $recipient)
  {
    if (!is_null($recipient)) {
      return [$recipient];
    }

    // Load notification and users
    /** @var \Kettari\TelegramBundle\Entity\Notification $doctrineNotification */
    $doctrineNotification = $this->doctrine->getRepository(
      'KettariTelegramBundle:Notification'
    )
      ->findOneByName($notification);
    if (is_null($doctrineNotification)) {
      return [];
    }

    return $doctrineNotification->getUsers();
  }

  /**
   * {@inheritdoc}
   */
  public function bumpQueue()
  {
    $stopwatch = new Stopwatch();
    $stopwatch->start('bumpQueue');
    $itemsCount = 0;

    $queue = $this->doctrine->getRepository(
      'KettariTelegramBundle:Queue'
    )
      ->findPending();

    /** @var Queue $queueItem */
    foreach ($queue as $queueItem) {
      try {

        $this->communicator->sendMessage(
          $queueItem->getChat()
            ->getTelegramId(),
          $queueItem->getText(),
          $queueItem->getParseMode(),
          is_null(
            $queueItem->getReplyMarkup()
          ) ? null : unserialize($queueItem->getReplyMarkup()),
          $queueItem->getDisableWebPagePreview(),
          $queueItem->getDisableNotification()
        );
        $queueItem->setStatus('sent');

      } catch (\Exception $e) {

        $queueItem->setStatus('error')
          ->setExceptionMessage($e->getMessage());
        $this->logger->error(
          'Exception while sending queued message #{id}: {exception_message}',
          [
            'id'                => $queueItem->getId(),
            'exception_message' => $e->getMessage(),
          ]
        );

      } finally {

        $itemsCount++;
        $queueItem->setUpdated(new \DateTime('now', new \DateTimeZone('UTC')));

      }
    }
    $this->doctrine->getManager()
      ->flush();

    $bumpEvent = $stopwatch->stop('bumpQueue');
    $elapsed = ($bumpEvent->getDuration() / 1000);
    $this->logger->info(
      'Bumped queue: processed {items_count} items, elapsed {time_elapsed} seconds. Average items per second: {items_per_second}',
      [
        'items_count'      => $itemsCount,
        'time_elapsed'     => sprintf('%.2f', $elapsed),
        'items_per_second' => sprintf(
          '%.2f',
          ($elapsed > 0) ? ($itemsCount / $elapsed) : 0
        ),
      ]
    );
  }
}