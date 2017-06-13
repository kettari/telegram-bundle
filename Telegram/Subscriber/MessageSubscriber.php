<?php
/**
 * Created by PhpStorm.
 * User: ant
 * Date: 25.04.2017
 * Time: 14:19
 */

namespace Kaula\TelegramBundle\Telegram\Subscriber;


use Exception;
use Kaula\TelegramBundle\Telegram\Bot;
use Kaula\TelegramBundle\Telegram\Event\GroupCreatedEvent;
use Kaula\TelegramBundle\Telegram\Event\JoinChatMemberEvent;
use Kaula\TelegramBundle\Telegram\Event\LeftChatMemberEvent;
use Kaula\TelegramBundle\Telegram\Event\MessageReceivedEvent;
use Kaula\TelegramBundle\Telegram\Event\MigrateFromChatIdEvent;
use Kaula\TelegramBundle\Telegram\Event\MigrateToChatIdEvent;
use Kaula\TelegramBundle\Telegram\Event\TextReceivedEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use unreal4u\TelegramAPI\Telegram\Types\ReplyKeyboardRemove;

class MessageSubscriber extends AbstractBotSubscriber implements EventSubscriberInterface
{
  const EMOJI_TRY_AGAIN = "\xF0\x9F\x99\x83";

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
      MessageReceivedEvent::NAME => [
        ['onMessageReceived'],
        ['onMessageCheckUnhandled', -90000],
      ],
    ];
  }

  /**
   * Processes incoming telegram message.
   *
   * @param MessageReceivedEvent $event
   */
  public function onMessageReceived(MessageReceivedEvent $event)
  {
    /** @var LoggerInterface $l */
    $l = $this->getBot()
      ->getContainer()
      ->get('logger');

    try {
      // Detect message type
      $message_type = $this->getBot()
        ->whatMessageType($event->getMessage());
      $l->info(
        'Message type: "{type_title}"',
        [
          'type_title' => $this->getBot()
            ->getMessageTypeTitle($message_type),
          'type'       => $message_type,
        ]
      );

      // Dispatch specific message types: text, document, audio, etc.
      $this->dispatchSpecificMessageTypes($event, $message_type);

    } catch (Exception $e) {
      $l->critical(
        'Exception while handling update with message: {error_message}',
        ['error_message' => $e->getMessage(), 'error_object' => $e]
      );
      $this->sendVerboseReply($event, $e);
    }
  }

  /**
   * Dispatches specific message types.
   *
   * @param MessageReceivedEvent $event
   * @param integer $message_type
   */
  private function dispatchSpecificMessageTypes(
    MessageReceivedEvent $event,
    $message_type
  ) {
    $dispatcher = $this->getBot()
      ->getEventDispatcher();

    // Dispatch text event
    if ($message_type & Bot::MT_TEXT) {
      $text_received_event = new TextReceivedEvent($event->getUpdate());
      $dispatcher->dispatch(TextReceivedEvent::NAME, $text_received_event);
    }
    // Dispatch chat member joined event
    if ($message_type & Bot::MT_NEW_CHAT_MEMBER) {
      $join_member_event = new JoinChatMemberEvent($event->getUpdate());
      $dispatcher->dispatch(JoinChatMemberEvent::NAME, $join_member_event);
    }
    // Dispatch chat member left event
    if ($message_type & Bot::MT_LEFT_CHAT_MEMBER) {
      $left_member_event = new LeftChatMemberEvent($event->getUpdate());
      $dispatcher->dispatch(LeftChatMemberEvent::NAME, $left_member_event);
    }
    // Dispatch group created event
    if ($message_type & Bot::MT_GROUP_CHAT_CREATED) {
      $group_created_event = new GroupCreatedEvent($event->getUpdate());
      $dispatcher->dispatch(GroupCreatedEvent::NAME, $group_created_event);
    }
    // Dispatch migration to chat ID event
    if ($message_type & Bot::MT_MIGRATE_TO_CHAT_ID) {
      $migrate_to_chat_event = new MigrateToChatIdEvent($event->getUpdate());
      $dispatcher->dispatch(MigrateToChatIdEvent::NAME, $migrate_to_chat_event);
    }
    // Dispatch migration from chat ID event
    if ($message_type & Bot::MT_MIGRATE_FROM_CHAT_ID) {
      $migrate_from_chat_event = new MigrateFromChatIdEvent(
        $event->getUpdate()
      );
      $dispatcher->dispatch(
        MigrateFromChatIdEvent::NAME,
        $migrate_from_chat_event
      );
    }
  }

  /**
   * Tries to send verbose message if debug environment detected.
   *
   * @param MessageReceivedEvent $event
   * @param \Exception $e
   */
  private function sendVerboseReply(MessageReceivedEvent $event, Exception $e)
  {
    try {
      $this->getBot()
        ->sendMessage(
          $event->getMessage()->chat->id,
          'На сервере произошла ошибка, пожалуйста, сообщите системному администратору.'
        );

      if ('dev' == $this->getBot()
          ->getContainer()
          ->getParameter('kernel.environment')
      ) {
        $this->getBot()
          ->sendMessage(
            $event->getMessage()->chat->id,
            $e->getMessage()
          );
      }
    } catch (Exception $passthrough) {
      // Do nothing
    }
  }

  /**
   * Handles situation when user sent us message and it is not handled.
   *
   * @param \Kaula\TelegramBundle\Telegram\Event\MessageReceivedEvent $event
   */
  public function onMessageCheckUnhandled(MessageReceivedEvent $event)
  {
    if (!$this->getBot()
      ->isRequestHandled()
    ) {
      $l = $this->getBot()
        ->getContainer()
        ->get('logger');
      $l->info('Request was not handled');

      // Tell user we do not understand him/her
      $this->getBot()
        ->sendMessage(
          $event->getMessage()->chat->id,
          'Извините, я не понял, что вы имели в виду '.self::EMOJI_TRY_AGAIN.
          ' Попробуйте начать с команды /help',
          null,
          new ReplyKeyboardRemove()
        );
    }
  }

}