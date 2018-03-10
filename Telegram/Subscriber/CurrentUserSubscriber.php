<?php
/**
 * Created by PhpStorm.
 * User: ant
 * Date: 25.04.2017
 * Time: 14:19
 */

namespace Kettari\TelegramBundle\Telegram\Subscriber;


use Kettari\TelegramBundle\Telegram\Event\UpdateReceivedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CurrentUserSubscriber extends AbstractBotSubscriber implements EventSubscriberInterface
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
    return [UpdateReceivedEvent::NAME => ['onUpdateReceived', 80000]];
  }

  /**
   * Resolves telegram user into database object and stores it for the future
   * use.
   *
   * @param UpdateReceivedEvent $e
   */
  public function onUpdateReceived(UpdateReceivedEvent $e)
  {
    $user_hq = $this->getBot()
      ->getUserHq();
    // Resolve current user
    $user_hq->resolveCurrentUser($e->getUpdate());
    // If current user is blocked, stop event propagation >:E
    if ($user_hq->isUserBlocked()) {

      // First things first
      $e->stopPropagation();
      // Then
      if (!is_null($e->getUpdate()->message)) {
        $this->getBot()
          ->sendMessage(
            $e->getUpdate()->message->chat->id,
            'Извините, ваш аккаунт заблокирован.'
          );
      }

    }
  }


}