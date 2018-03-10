<?php
/**
 * Created by PhpStorm.
 * User: ant
 * Date: 25.04.2017
 * Time: 14:19
 */

namespace Kettari\TelegramBundle\Telegram\Subscriber;


use Exception;
use Kettari\TelegramBundle\Telegram\Bot;
use Kettari\TelegramBundle\Telegram\Event\AudioReceivedEvent;
use Kettari\TelegramBundle\Telegram\Event\ChatDeletePhotoEvent;
use Kettari\TelegramBundle\Telegram\Event\ChatNewPhotoEvent;
use Kettari\TelegramBundle\Telegram\Event\ChatNewTitleEvent;
use Kettari\TelegramBundle\Telegram\Event\ContactReceivedEvent;
use Kettari\TelegramBundle\Telegram\Event\DocumentReceivedEvent;
use Kettari\TelegramBundle\Telegram\Event\GameReceivedEvent;
use Kettari\TelegramBundle\Telegram\Event\GroupCreatedEvent;
use Kettari\TelegramBundle\Telegram\Event\JoinChatMembersManyEvent;
use Kettari\TelegramBundle\Telegram\Event\LeftChatMemberEvent;
use Kettari\TelegramBundle\Telegram\Event\LocationReceivedEvent;
use Kettari\TelegramBundle\Telegram\Event\MessagePinnedEvent;
use Kettari\TelegramBundle\Telegram\Event\MessageReceivedEvent;
use Kettari\TelegramBundle\Telegram\Event\MigrateFromChatIdEvent;
use Kettari\TelegramBundle\Telegram\Event\MigrateToChatIdEvent;
use Kettari\TelegramBundle\Telegram\Event\PaymentInvoiceEvent;
use Kettari\TelegramBundle\Telegram\Event\PaymentSuccessfulEvent;
use Kettari\TelegramBundle\Telegram\Event\PhotoReceivedEvent;
use Kettari\TelegramBundle\Telegram\Event\StickerReceivedEvent;
use Kettari\TelegramBundle\Telegram\Event\TextReceivedEvent;
use Kettari\TelegramBundle\Telegram\Event\VenueReceivedEvent;
use Kettari\TelegramBundle\Telegram\Event\VideoNoteReceivedEvent;
use Kettari\TelegramBundle\Telegram\Event\VideoReceivedEvent;
use Kettari\TelegramBundle\Telegram\Event\VoiceReceivedEvent;
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
        'Detected message type: "{type_title}"',
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
    // Dispatch audio event
    if ($message_type & Bot::MT_AUDIO) {
      $audio_received_event = new AudioReceivedEvent($event->getUpdate());
      $dispatcher->dispatch(AudioReceivedEvent::NAME, $audio_received_event);
    }
    // Dispatch document event
    if ($message_type & Bot::MT_DOCUMENT) {
      $document_received_event = new DocumentReceivedEvent($event->getUpdate());
      $dispatcher->dispatch(
        DocumentReceivedEvent::NAME,
        $document_received_event
      );
    }
    // Dispatch game event
    if ($message_type & Bot::MT_GAME) {
      $game_received_event = new GameReceivedEvent($event->getUpdate());
      $dispatcher->dispatch(GameReceivedEvent::NAME, $game_received_event);
    }
    // Dispatch photo event
    if ($message_type & Bot::MT_PHOTO) {
      $photo_received_event = new PhotoReceivedEvent($event->getUpdate());
      $dispatcher->dispatch(PhotoReceivedEvent::NAME, $photo_received_event);
    }
    // Dispatch sticker event
    if ($message_type & Bot::MT_STICKER) {
      $sticker_received_event = new StickerReceivedEvent($event->getUpdate());
      $dispatcher->dispatch(
        StickerReceivedEvent::NAME,
        $sticker_received_event
      );
    }
    // Dispatch video event
    if ($message_type & Bot::MT_VIDEO) {
      $video_received_event = new VideoReceivedEvent($event->getUpdate());
      $dispatcher->dispatch(VideoReceivedEvent::NAME, $video_received_event);
    }
    // Dispatch voice event
    if ($message_type & Bot::MT_VOICE) {
      $voice_received_event = new VoiceReceivedEvent($event->getUpdate());
      $dispatcher->dispatch(VoiceReceivedEvent::NAME, $voice_received_event);
    }
    // Dispatch contact event
    if ($message_type & Bot::MT_CONTACT) {
      $contact_received_event = new ContactReceivedEvent($event->getUpdate());
      $dispatcher->dispatch(
        ContactReceivedEvent::NAME,
        $contact_received_event
      );
    }
    // Dispatch location event
    if ($message_type & Bot::MT_LOCATION) {
      $location_received_event = new LocationReceivedEvent($event->getUpdate());
      $dispatcher->dispatch(
        LocationReceivedEvent::NAME,
        $location_received_event
      );
    }
    // Dispatch venue event
    if ($message_type & Bot::MT_VENUE) {
      $venue_received_event = new VenueReceivedEvent($event->getUpdate());
      $dispatcher->dispatch(VenueReceivedEvent::NAME, $venue_received_event);
    }

    // Dispatch chat member joined event
    // NB: 'new_chat_member' property of the Message object deprecated since May 18, 2017
    // and should not be used (see https://core.telegram.org/bots/api-changelog#may-18-2017)
    /*if ($message_type & Bot::MT_NEW_CHAT_MEMBER) {
      $join_member_event = new JoinChatMemberEvent($event->getUpdate());
      $dispatcher->dispatch(JoinChatMemberEvent::NAME, $join_member_event);
    }*/

    // Dispatch chat member joined event
    if ($message_type & Bot::MT_NEW_CHAT_MEMBERS_MANY) {
      $join_members_many_event = new JoinChatMembersManyEvent(
        $event->getUpdate()
      );
      $dispatcher->dispatch(
        JoinChatMembersManyEvent::NAME,
        $join_members_many_event
      );
    }
    // Dispatch chat member left event
    if ($message_type & Bot::MT_LEFT_CHAT_MEMBER) {
      $left_member_event = new LeftChatMemberEvent($event->getUpdate());
      $dispatcher->dispatch(LeftChatMemberEvent::NAME, $left_member_event);
    }
    // Dispatch new chat title event
    if ($message_type & Bot::MT_NEW_CHAT_TITLE) {
      $new_chat_title_event = new ChatNewTitleEvent($event->getUpdate());
      $dispatcher->dispatch(ChatNewTitleEvent::NAME, $new_chat_title_event);
    }
    // Dispatch new chat photo event
    if ($message_type & Bot::MT_NEW_CHAT_PHOTO) {
      $new_chat_photo_event = new ChatNewPhotoEvent($event->getUpdate());
      $dispatcher->dispatch(ChatNewPhotoEvent::NAME, $new_chat_photo_event);
    }
    // Dispatch chat photo deleted event
    if ($message_type & Bot::MT_DELETE_CHAT_PHOTO) {
      $delete_chat_photo_event = new ChatDeletePhotoEvent($event->getUpdate());
      $dispatcher->dispatch(
        ChatDeletePhotoEvent::NAME,
        $delete_chat_photo_event
      );
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
    // Dispatch message pinned event
    if ($message_type & Bot::MT_PINNED_MESSAGE) {
      $message_pinned_event = new MessagePinnedEvent($event->getUpdate());
      $dispatcher->dispatch(MessagePinnedEvent::NAME, $message_pinned_event);
    }
    // Dispatch successful payment event
    if ($message_type & Bot::MT_SUCCESSFUL_PAYMENT) {
      $successful_payment_event = new PaymentSuccessfulEvent(
        $event->getUpdate()
      );
      $dispatcher->dispatch(
        PaymentSuccessfulEvent::NAME,
        $successful_payment_event
      );
    }
    // Dispatch invoice event
    if ($message_type & Bot::MT_INVOICE) {
      $invoice_event = new PaymentInvoiceEvent($event->getUpdate());
      $dispatcher->dispatch(PaymentInvoiceEvent::NAME, $invoice_event);
    }
    // Dispatch video note event
    if ($message_type & Bot::MT_VIDEO_NOTE) {
      $video_note_event = new VideoNoteReceivedEvent($event->getUpdate());
      $dispatcher->dispatch(VideoNoteReceivedEvent::NAME, $video_note_event);
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
   * @param \Kettari\TelegramBundle\Telegram\Event\MessageReceivedEvent $event
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