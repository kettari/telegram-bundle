<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram;


use Kettari\TelegramBundle\Entity\Queue;
use Kettari\TelegramBundle\Entity\User;
use Kettari\TelegramBundle\Telegram\Command\AbstractCommand;
use Kettari\TelegramBundle\Telegram\Command\TelegramCommandInterface;
use Kettari\TelegramBundle\Telegram\Event\TerminateEvent;
use Kettari\TelegramBundle\Telegram\Event\UpdateReceivedEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Stopwatch\Stopwatch;
use unreal4u\TelegramAPI\Abstracts\KeyboardMethods;
use unreal4u\TelegramAPI\Abstracts\TelegramMethods;
use unreal4u\TelegramAPI\Telegram\Types\Update;

class Bot implements BotInterface
{
  /**
   * Logger interface.
   *
   * @var LoggerInterface
   */
  private $logger;

  /**
   * @var EventDispatcherInterface
   */
  private $dispatcher;

  /**
   * @var \Kettari\TelegramBundle\Telegram\CommandBusInterface
   */
  private $bus;

  /**
   * Bot constructor.
   *
   * @param \Psr\Log\LoggerInterface $logger
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher
   * @param \Kettari\TelegramBundle\Telegram\CommandBusInterface $bus
   */
  public function __construct(
    LoggerInterface $logger,
    EventDispatcherInterface $eventDispatcher,
    CommandBusInterface $bus
  ) {
    $this->logger = $logger;
    $this->dispatcher = $eventDispatcher;
    $this->bus = $bus;
  }

  /**
   * Adds subscriber to the dispatcher. See services.yml for the list of
   * active subscribers.
   *
   * @param \Symfony\Component\EventDispatcher\EventSubscriberInterface $subscriber
   */
  public function addSubscriber(EventSubscriberInterface $subscriber)
  {
    $this->dispatcher->addSubscriber($subscriber);
  }

  /**
   * Adds default commands to the CommandBus.
   *
   * @return Bot
   */
  public function addCommand()
  {

  }
  /*public function addDefaultCommands()
  {
    $this->bus
      ->registerCommand(StartCommand::class)
      ->registerCommand(SettingsCommand::class)
      ->registerCommand(HelpCommand::class)
      ->registerCommand(ListRolesCommand::class)
      ->registerCommand(PushCommand::class)
      ->registerCommand(UserManCommand::class);

    return $this;
  }*/

  /**
   * Handles update.
   *
   * @param \unreal4u\TelegramAPI\Telegram\Types\Update $update
   * @return null|TelegramMethods
   */
  public function handle(Update $update)
  {
    $this->logger->debug(
      'About to handle update ID={update_id}',
      ['update_id' => $update->update_id]
    );

    // Dispatch event when we got the Update object
    $updateReceivedEvent = new UpdateReceivedEvent($update);
    $this->dispatcher->dispatch(
      UpdateReceivedEvent::NAME,
      $updateReceivedEvent
    );

    // Dispatch termination
    $terminateEvent = new TerminateEvent($update);
    if ($updateReceivedEvent->getResponse()) {
      $terminateEvent->setResponse($updateReceivedEvent->getResponse());
    }
    $this->dispatcher->dispatch(TerminateEvent::NAME, $terminateEvent);

    $this->logger->debug(
      'Update ID={update_id} handled',
      ['update_id' => $update->update_id]
    );

    return $updateReceivedEvent->getResponse();
  }

  /**
   * Push notification to users.
   *
   * @param string $notification Notification name. If $recipient is specified,
   *   this option is ignored.
   * @param string $text Text of the message to be sent
   * @param string $parseMode Send Markdown or HTML, if you want Telegram apps
   *   to show bold, italic, fixed-width text or inline URLs in your bot's
   *   message.
   * @param KeyboardMethods $replyMarkup Additional interface options. A
   *   JSON-serialized object for an inline keyboard, custom reply keyboard,
   *   instructions to remove reply keyboard or to force a reply from the user.
   * @param bool $disableWebPagePreview Disables link previews for links in
   *   this message
   * @param bool $disableNotification Sends the message silently. iOS users
   *   will not receive a notification, Android users will receive a
   *   notification with no sound.
   * @param \Kettari\TelegramBundle\Entity\User|null $recipient If specified,
   *   send notification only to this user.
   * @param string|null $chatId Only if $recipient is specified: use this chat
   *   instead of private. If skipped, message is send privately
   */
  public function pushNotification(
    $notification,
    $text,
    $parseMode = '',
    $replyMarkup = null,
    $disableWebPagePreview = false,
    $disableNotification = false,
    User $recipient = null,
    $chatId = null
  ) {
    $now = new \DateTime('now', new \DateTimeZone('UTC'));
    $d = $this->getContainer()
      ->get('doctrine');
    /** @var LoggerInterface $l */
    $l = $this->getContainer()
      ->get('logger');

    $l->info(
      'Pushing notification "{notification}"',
      [
        'notification'     => $notification,
        'telegram_user_id' => !is_null($recipient) ? $recipient->getTelegramId(
        ) : '(all)',
        'chat_id'          => $chatId,
      ]
    );

    $subscribers = $this->getEligibleSubscribers($notification, $recipient);
    $l->info(
      'Subscribers to receive notification: {subscribers_count}',
      ['subscribers_count' => count($subscribers)]
    );

    /** @var \Kettari\TelegramBundle\Entity\User $userItem */
    foreach ($subscribers as $userItem) {
      if ($userItem->isBlocked()) {
        continue;
      }

      if (is_null(
        $chat = $d->getRepository('KettariTelegramBundle:Chat')
          ->findOneBy(
            [
              'telegram_id' => is_null($chatId) ? $userItem->getTelegramId(
              ) : $chatId,
            ]
          )
      )) {
        $l->error(
          sprintf(
            'Queue for push failed: unable to find chat for given user (telegram_user_id=%s)',
            $userItem->getTelegramId()
          )
        );

        return;
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
      $d->getManager()
        ->persist($queue);
    }
    $d->getManager()
      ->flush();
  }

  /**
   * Returns array of subscribers who will receive notification.
   *
   * @param string $notification Name of the notification
   * @param User $recipient Recipient who intended to receive notification
   * @return array|\Doctrine\Common\Collections\Collection
   */
  private function getEligibleSubscribers($notification, $recipient)
  {
    if (!is_null($recipient)) {
      return [$recipient];
    }

    // Load notification and users
    /** @var \Kettari\TelegramBundle\Entity\Notification $doctrineNotification */
    $doctrineNotification = $this->getContainer()
      ->get('doctrine')
      ->getRepository(
        'KettariTelegramBundle:Notification'
      )
      ->findOneBy(['name' => $notification]);
    if (is_null($doctrineNotification)) {
      return [];
    }

    return $doctrineNotification->getUsers();
  }

  /**
   * Sends part of queued messages.
   *
   * @param int $bumpSize Count of items to send in this bump operation
   */
  public function bumpQueue($bumpSize = 10)
  {
    $stopwatch = new Stopwatch();
    $stopwatch->start('bumpQueue');
    /** @var LoggerInterface $l */
    $l = $this->getContainer()
      ->get('logger');
    $itemsCount = 0;

    $queue = $this->getContainer()
      ->get('doctrine')
      ->getRepository(
        'KettariTelegramBundle:Queue'
      )
      ->findBy(['status' => 'pending'], ['created' => 'ASC'], $bumpSize);

    /** @var Queue $queueItem */
    foreach ($queue as $queueItem) {
      try {

        $this->sendMessage(
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
        $l->warning(
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
    $this->getContainer()
      ->get('doctrine')
      ->getManager()
      ->flush();

    $bumpEvent = $stopwatch->stop('bumpQueue');
    $elapsed = ($bumpEvent->getDuration() / 1000);
    $l->info(
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

  /**
   * Returns logger object.
   *
   * @return LoggerInterface
   */
  public function getLogger()
  {
    return $this->logger;
  }

}