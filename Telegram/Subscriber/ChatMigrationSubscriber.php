<?php
/**
 * Created by PhpStorm.
 * User: ant
 * Date: 25.04.2017
 * Time: 14:19
 */

namespace Kettari\TelegramBundle\Telegram\Subscriber;


use Kettari\TelegramBundle\Exception\TelegramBundleException;
use Kettari\TelegramBundle\Telegram\Event\MigrateFromChatIdEvent;
use Kettari\TelegramBundle\Telegram\Event\MigrateToChatIdEvent;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ChatMigrationSubscriber extends AbstractBotSubscriber implements EventSubscriberInterface
{
  /**
   * @var RegistryInterface
   */
  protected $doctrine;

  /**
   * GroupSubscriber constructor.
   *
   * @param \Psr\Log\LoggerInterface $logger
   * @param \Symfony\Bridge\Doctrine\RegistryInterface $doctrine
   */
  public function __construct(
    LoggerInterface $logger,
    RegistryInterface $doctrine
  ) {
    parent::__construct($logger);
    $this->doctrine = $doctrine;
  }

  /**
   * Returns an array of event names this subscriber wants to listen to.
   *
   * The array keys are event names and the value can be:
   *
   *  * The method name to call (priority defaults to 0)
   *  * An array composed of the method name to call and the priority
   *  * An array of arrays composed of the method names to call and respective
   *    priorities, or 0 if unset
   *
   * For instance:
   *
   *  * array('eventName' => 'methodName')
   *  * array('eventName' => array('methodName', $priority))
   *  * array('eventName' => array(array('methodName1', $priority),
   * array('methodName2')))
   *
   * @return array The event names to listen to
   */
  public static function getSubscribedEvents()
  {
    return [
      MigrateToChatIdEvent::NAME => 'onMigrateToChatId',
      MigrateFromChatIdEvent::NAME => 'onMigrateFromChatId',
    ];
  }

  /**
   * Processes migration to chat ID.
   *
   * @param MigrateToChatIdEvent $event
   */
  public function onMigrateToChatId(MigrateToChatIdEvent $event)
  {
    $this->logger->debug(
      'Processing ChatMigrationSubscriber::MigrateToChatIdEvent for the message ID={message_id}',
      ['message_id' => $event->getMessage()->message_id]
    );

    // Get telegram chat ids
    $chatFromId = $event->getMessage()->chat->id;
    $chatToId = $event->getMessage()->migrate_to_chat_id;

    // Find chat object
    $chat = $this->doctrine->getRepository('KettariTelegramBundle:Chat')
      ->findOneByTelegramId($chatFromId);
    if (!$chat) {
      throw new TelegramBundleException(
        'Chat entity not found for Telegram chat ID='.$chatFromId
      );
    }
    $chat->setTelegramId($chatToId);
    // Commit changes
    $this->doctrine->getManager()
      ->flush();

    // Tell the Bot this request is handled
    $event->getKeeper()
      ->setRequestHandled(true);
    $this->logger->notice(
      'Migrated to chat with id "{chat_id}"',
      ['chat_id' => $chatToId]
    );

    $this->logger->info(
      'ChatMigrationSubscriber::MigrateToChatIdEvent for the message ID={message_id} processed',
      ['message_id' => $event->getMessage()->message_id]
    );
  }

  /**
   * Processes migration from chat ID.
   *
   * @param MigrateFromChatIdEvent $event
   */
  public function onMigrateFromChatId(MigrateFromChatIdEvent $event)
  {
    $this->logger->debug(
      'Processing ChatMigrationSubscriber::MigrateFromChatIdEvent for the message ID={message_id}',
      ['message_id' => $event->getMessage()->message_id]
    );

    // Tell the Bot this request is handled
    $event->getKeeper()
      ->setRequestHandled(true);
    $this->logger->notice(
      'Migrated from chat with id "{chat_id}"',
      ['chat_id' => $event->getMessage()->chat->id]
    );

    $this->logger->info(
      'ChatMigrationSubscriber::MigrateFromChatIdEvent for the message ID={message_id} processed',
      ['message_id' => $event->getMessage()->message_id]
    );
  }
}