<?php
/**
 * Created by PhpStorm.
 * User: ant
 * Date: 25.04.2017
 * Time: 14:19
 */

namespace Kaula\TelegramBundle\Telegram\Subscriber;


use Kaula\TelegramBundle\Telegram\Event\UserRegisteredEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class UserRegistrationSubscriber extends AbstractBotSubscriber implements EventSubscriberInterface
{

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
   * @param \Kaula\TelegramBundle\Telegram\Event\UserRegisteredEvent $e
   */
  public function onUserRegistered(UserRegisteredEvent $e)
  {
    $this->getBot()
      ->pushNotification(
        self::NOTIFICATION_NEW_REGISTER,
        sprintf(
          '%s Новая регистрация: %s (#%s)',
          self::NEW_REGISTER_EMOJI,
          trim(
            $e->getRegisteredUser()
              ->getLastName().' '.$e->getRegisteredUser()
              ->getFirstName()
          ),
          $e->getRegisteredUser()
            ->getTelegramId()
        )
      );
    $this->getBot()
      ->bumpQueue();
  }


}