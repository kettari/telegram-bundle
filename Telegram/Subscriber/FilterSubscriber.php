<?php
/**
 * Created by PhpStorm.
 * User: ant
 * Date: 25.04.2017
 * Time: 14:19
 */

namespace Kettari\TelegramBundle\Telegram\Subscriber;


use InvalidArgumentException;
use Kettari\TelegramBundle\Telegram\Event\UpdateIncomingEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use unreal4u\TelegramAPI\Telegram\Types\Update;

class FilterSubscriber implements EventSubscriberInterface
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
    return [UpdateIncomingEvent::NAME => 'onUpdateIncoming'];
  }

  /**
   * Handles incoming data and provides Update object.
   *
   * @param \Kettari\TelegramBundle\Telegram\Event\UpdateIncomingEvent $e
   */
  public function onUpdateIncoming(UpdateIncomingEvent $e)
  {
    $e->setUpdate($this->scrapIncomingData());
  }

  /**
   * Scrap incoming data into Update object.
   *
   * @return Update
   */
  private function scrapIncomingData()
  {
    $update_data = json_decode(file_get_contents('php://input'), true);
    if (JSON_ERROR_NONE != json_last_error()) {
      throw new InvalidArgumentException('JSON error: '.json_last_error_msg());
    }

    return new Update($update_data);
  }


}