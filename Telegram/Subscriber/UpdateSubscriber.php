<?php
declare(strict_types=1);

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
use Kettari\TelegramBundle\Telegram\Event\UpdateReceivedEvent;
use Kettari\TelegramBundle\Telegram\Event\VenueReceivedEvent;
use Kettari\TelegramBundle\Telegram\Event\VideoNoteReceivedEvent;
use Kettari\TelegramBundle\Telegram\Event\VideoReceivedEvent;
use Kettari\TelegramBundle\Telegram\Event\VoiceReceivedEvent;
use Kettari\TelegramBundle\Telegram\UpdateTypeResolver;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use unreal4u\TelegramAPI\Telegram\Types\ReplyKeyboardRemove;

class UpdateSubscriber extends AbstractBotSubscriber implements EventSubscriberInterface
{
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
      UpdateReceivedEvent::NAME => ['onUpdateReceived'],
    ];
  }

  /**
   * Processes update and tries to dispatch Message event.
   *
   * @param \Kettari\TelegramBundle\Telegram\Event\UpdateReceivedEvent $event
   */
  public function onUpdateReceived(UpdateReceivedEvent $event)
  {
    // Get update type
    $updateType = UpdateTypeResolver::getUpdateType($event->getUpdate());
    $this->logger->info(
      'Handling update of type "{type}"',
      ['type' => $updateType, 'update' => $event->getUpdate()]
    );

    // Check type of the update and dispatch more specific events
    switch ($updateType) {
      case UpdateTypeResolver::UT_MESSAGE:
        $messageReceivedEvent = new MessageReceivedEvent($event->getUpdate());
        $this->dispatcher->dispatch(
          MessageReceivedEvent::NAME,
          $messageReceivedEvent
        );
        break;
    }
  }
}