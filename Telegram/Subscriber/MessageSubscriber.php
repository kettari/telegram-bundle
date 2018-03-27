<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram\Subscriber;


use Exception;
use Kettari\TelegramBundle\Telegram\Communicator;
use Kettari\TelegramBundle\Telegram\CommunicatorInterface;
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
use Kettari\TelegramBundle\Telegram\MessageTypeResolver;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MessageSubscriber extends AbstractBotSubscriber implements EventSubscriberInterface
{
  const EMOJI_TRY_AGAIN = "\xF0\x9F\x99\x83";

  /**
   * @var EventDispatcherInterface
   */
  private $dispatcher;

  /**
   * @var CommunicatorInterface
   */
  private $communicator;

  /**
   * HookerSubscriber constructor.
   *
   * @param \Psr\Log\LoggerInterface $logger
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher
   * @param \Kettari\TelegramBundle\Telegram\CommunicatorInterface $communicator
   */
  public function __construct(
    LoggerInterface $logger,
    EventDispatcherInterface $dispatcher,
    CommunicatorInterface $communicator
  ) {
    parent::__construct($logger);
    $this->dispatcher = $dispatcher;
    $this->communicator = $communicator;
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
    $this->logger->debug(
      'Processing MessageSubscriber::MessageReceivedEvent for message ID={message_id} in the chat ID={chat_id}',
      [
        'message_id' => $event->getMessage()->message_id,
        'chat_id'    => $event->getMessage()->chat->id,
        'message'    => $event->getMessage(),
      ]
    );

    try {
      // Detect message type
      $messageType = MessageTypeResolver::getMessageType($event->getMessage());
      $this->logger->debug(
        'Detected message type: "{type_title}"',
        [
          'type_title' => MessageTypeResolver::getMessageTypeTitle(
            $messageType
          ),
          'type'       => $messageType,
        ]
      );

      // Dispatch specific message types: text, document, audio, etc.
      $this->dispatchSpecificMessageTypes($event, $messageType);

    } catch (Exception $e) {
      $this->logger->critical(
        'Exception while handling update with message ID={message_id} in the chat ID={chat_id}: {error_message}',
        [
          'error_message' => $e->getMessage(),
          'error_object'  => $e,
          'message_id'    => $event->getMessage()->message_id,
          'chat_id'       => $event->getMessage()->chat->id,
        ]
      );
      $this->communicator->sendMessage(
        $event->getMessage()->chat->id,
        'На сервере произошла ошибка, пожалуйста, сообщите системному администратору.',
        Communicator::PARSE_MODE_PLAIN
      );
    }

    $this->logger->info(
      'MessageSubscriber::MessageReceivedEvent for message ID={message_id} in the chat ID={chat_id} processed',
      [
        'message_id' => $event->getMessage()->message_id,
        'chat_id'    => $event->getMessage()->chat->id,
      ]
    );
  }

  /**
   * Dispatches specific message types.
   *
   * @param MessageReceivedEvent $event
   * @param integer $messageType
   */
  private function dispatchSpecificMessageTypes(
    MessageReceivedEvent $event,
    int $messageType
  ) {
    // Dispatch text event
    if ($messageType & MessageTypeResolver::MT_TEXT) {
      $textReceivedEvent = new TextReceivedEvent($event->getUpdate());
      $this->dispatcher->dispatch(TextReceivedEvent::NAME, $textReceivedEvent);
    }
    // Dispatch audio event
    if ($messageType & MessageTypeResolver::MT_AUDIO) {
      $audio_received_event = new AudioReceivedEvent($event->getUpdate());
      $this->dispatcher->dispatch(
        AudioReceivedEvent::NAME,
        $audio_received_event
      );
    }
    // Dispatch document event
    if ($messageType & MessageTypeResolver::MT_DOCUMENT) {
      $document_received_event = new DocumentReceivedEvent($event->getUpdate());
      $this->dispatcher->dispatch(
        DocumentReceivedEvent::NAME,
        $document_received_event
      );
    }
    // Dispatch game event
    if ($messageType & MessageTypeResolver::MT_GAME) {
      $game_received_event = new GameReceivedEvent($event->getUpdate());
      $this->dispatcher->dispatch(
        GameReceivedEvent::NAME,
        $game_received_event
      );
    }
    // Dispatch photo event
    if ($messageType & MessageTypeResolver::MT_PHOTO) {
      $photo_received_event = new PhotoReceivedEvent($event->getUpdate());
      $this->dispatcher->dispatch(
        PhotoReceivedEvent::NAME,
        $photo_received_event
      );
    }
    // Dispatch sticker event
    if ($messageType & MessageTypeResolver::MT_STICKER) {
      $sticker_received_event = new StickerReceivedEvent($event->getUpdate());
      $this->dispatcher->dispatch(
        StickerReceivedEvent::NAME,
        $sticker_received_event
      );
    }
    // Dispatch video event
    if ($messageType & MessageTypeResolver::MT_VIDEO) {
      $video_received_event = new VideoReceivedEvent($event->getUpdate());
      $this->dispatcher->dispatch(
        VideoReceivedEvent::NAME,
        $video_received_event
      );
    }
    // Dispatch voice event
    if ($messageType & MessageTypeResolver::MT_VOICE) {
      $voice_received_event = new VoiceReceivedEvent($event->getUpdate());
      $this->dispatcher->dispatch(
        VoiceReceivedEvent::NAME,
        $voice_received_event
      );
    }
    // Dispatch contact event
    if ($messageType & MessageTypeResolver::MT_CONTACT) {
      $contact_received_event = new ContactReceivedEvent($event->getUpdate());
      $this->dispatcher->dispatch(
        ContactReceivedEvent::NAME,
        $contact_received_event
      );
    }
    // Dispatch location event
    if ($messageType & MessageTypeResolver::MT_LOCATION) {
      $location_received_event = new LocationReceivedEvent($event->getUpdate());
      $this->dispatcher->dispatch(
        LocationReceivedEvent::NAME,
        $location_received_event
      );
    }
    // Dispatch venue event
    if ($messageType & MessageTypeResolver::MT_VENUE) {
      $venue_received_event = new VenueReceivedEvent($event->getUpdate());
      $this->dispatcher->dispatch(
        VenueReceivedEvent::NAME,
        $venue_received_event
      );
    }

    // Dispatch chat member joined event
    // NB: 'new_chat_member' property of the Message object deprecated since May 18, 2017
    // and should not be used (see https://core.telegram.org/bots/api-changelog#may-18-2017)
    /*if ($message_type & MessageTypeResolver::MT_NEW_CHAT_MEMBER) {
      $join_member_event = new JoinChatMemberEvent($event->getUpdate());
      $this->dispatcher->dispatch(JoinChatMemberEvent::NAME, $join_member_event);
    }*/

    // Dispatch chat member joined event
    if ($messageType & MessageTypeResolver::MT_NEW_CHAT_MEMBERS_MANY) {
      $join_members_many_event = new JoinChatMembersManyEvent(
        $event->getUpdate()
      );
      $this->dispatcher->dispatch(
        JoinChatMembersManyEvent::NAME,
        $join_members_many_event
      );
    }
    // Dispatch chat member left event
    if ($messageType & MessageTypeResolver::MT_LEFT_CHAT_MEMBER) {
      $left_member_event = new LeftChatMemberEvent($event->getUpdate());
      $this->dispatcher->dispatch(
        LeftChatMemberEvent::NAME,
        $left_member_event
      );
    }
    // Dispatch new chat title event
    if ($messageType & MessageTypeResolver::MT_NEW_CHAT_TITLE) {
      $new_chat_title_event = new ChatNewTitleEvent($event->getUpdate());
      $this->dispatcher->dispatch(
        ChatNewTitleEvent::NAME,
        $new_chat_title_event
      );
    }
    // Dispatch new chat photo event
    if ($messageType & MessageTypeResolver::MT_NEW_CHAT_PHOTO) {
      $new_chat_photo_event = new ChatNewPhotoEvent($event->getUpdate());
      $this->dispatcher->dispatch(
        ChatNewPhotoEvent::NAME,
        $new_chat_photo_event
      );
    }
    // Dispatch chat photo deleted event
    if ($messageType & MessageTypeResolver::MT_DELETE_CHAT_PHOTO) {
      $delete_chat_photo_event = new ChatDeletePhotoEvent($event->getUpdate());
      $this->dispatcher->dispatch(
        ChatDeletePhotoEvent::NAME,
        $delete_chat_photo_event
      );
    }
    // Dispatch group created event
    if ($messageType & MessageTypeResolver::MT_GROUP_CHAT_CREATED) {
      $group_created_event = new GroupCreatedEvent($event->getUpdate());
      $this->dispatcher->dispatch(
        GroupCreatedEvent::NAME,
        $group_created_event
      );
    }
    // Dispatch migration to chat ID event
    if ($messageType & MessageTypeResolver::MT_MIGRATE_TO_CHAT_ID) {
      $migrate_to_chat_event = new MigrateToChatIdEvent($event->getUpdate());
      $this->dispatcher->dispatch(
        MigrateToChatIdEvent::NAME,
        $migrate_to_chat_event
      );
    }
    // Dispatch migration from chat ID event
    if ($messageType & MessageTypeResolver::MT_MIGRATE_FROM_CHAT_ID) {
      $migrate_from_chat_event = new MigrateFromChatIdEvent(
        $event->getUpdate()
      );
      $this->dispatcher->dispatch(
        MigrateFromChatIdEvent::NAME,
        $migrate_from_chat_event
      );
    }
    // Dispatch message pinned event
    if ($messageType & MessageTypeResolver::MT_PINNED_MESSAGE) {
      $message_pinned_event = new MessagePinnedEvent($event->getUpdate());
      $this->dispatcher->dispatch(
        MessagePinnedEvent::NAME,
        $message_pinned_event
      );
    }
    // Dispatch successful payment event
    if ($messageType & MessageTypeResolver::MT_SUCCESSFUL_PAYMENT) {
      $successful_payment_event = new PaymentSuccessfulEvent(
        $event->getUpdate()
      );
      $this->dispatcher->dispatch(
        PaymentSuccessfulEvent::NAME,
        $successful_payment_event
      );
    }
    // Dispatch invoice event
    if ($messageType & MessageTypeResolver::MT_INVOICE) {
      $invoice_event = new PaymentInvoiceEvent($event->getUpdate());
      $this->dispatcher->dispatch(PaymentInvoiceEvent::NAME, $invoice_event);
    }
    // Dispatch video note event
    if ($messageType & MessageTypeResolver::MT_VIDEO_NOTE) {
      $video_note_event = new VideoNoteReceivedEvent($event->getUpdate());
      $this->dispatcher->dispatch(
        VideoNoteReceivedEvent::NAME,
        $video_note_event
      );
    }

  }

  /**
   * Handles situation when user sent us message and it is not handled.
   *
   * @param \Kettari\TelegramBundle\Telegram\Event\MessageReceivedEvent $event
   * @obsolete
   */
  public function onMessageCheckUnhandled(MessageReceivedEvent $event)
  {
    // @TODO Consider to remove this method
  }

}