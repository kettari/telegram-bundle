<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram\Subscriber;


use Kettari\TelegramBundle\Telegram\Event\UserRegisteredEvent;
use Kettari\TelegramBundle\Telegram\UserHelperTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class UserRegistrationSubscriber extends AbstractBotSubscriber implements EventSubscriberInterface
{
  use UserHelperTrait;

  const NOTIFICATION_NEW_REGISTER = 'new-register';
  const NEW_REGISTER_EMOJI = "\xF0\x9F\x98\x8C";

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
    return [UserRegisteredEvent::NAME => 'onUserRegistered'];
  }

  /**
   * Pushes notification.
   *
   * @param \Kettari\TelegramBundle\Telegram\Event\UserRegisteredEvent $event
   */
  public function onUserRegistered(UserRegisteredEvent $event)
  {
    $this->pusher->pushNotification(
      self::NOTIFICATION_NEW_REGISTER,
      sprintf(
        '%s Новая регистрация: %s → %s',
        self::NEW_REGISTER_EMOJI,
        trim(
          $event->getRegisteredUser()
            ->getLastName().' '.$event->getRegisteredUser()
            ->getFirstName()
        ),
        self::formatUserName($event->getRegisteredUser())
      )
    );
    $this->pusher->bumpQueue();
  }


}