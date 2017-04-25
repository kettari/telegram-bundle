<?php
/**
 * Created by PhpStorm.
 * User: ant
 * Date: 25.04.2017
 * Time: 14:19
 */

namespace Kaula\TelegramBundle\Telegram\Subscriber;


use Kaula\TelegramBundle\Entity\Log;
use Kaula\TelegramBundle\Telegram\Event\UpdateReceivedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use unreal4u\TelegramAPI\Telegram\Types\Update;

class LogSubscriber extends AbstractBotSubscriber implements EventSubscriberInterface
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
    return [UpdateReceivedEvent::NAME => ['onUpdateReceived', 90000]];
  }

  /**
   * Writes incoming message to the log table in the database.
   *
   * @param UpdateReceivedEvent $e
   */
  public function onUpdateReceived(UpdateReceivedEvent $e)
  {
    $this->logInput($e->getUpdate());
  }

  /**
   * Logs input message to the database.
   *
   * @param \unreal4u\TelegramAPI\Telegram\Types\Update $update
   */
  private function logInput(Update $update)
  {
    $direction = 'in';
    $type = null;
    $chat_id = null;
    $content = null;

    $type = $this->getBot()
      ->whatUpdateType($update);
    $content = json_encode(
      get_object_vars($update),
      JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
    );
    if (!is_null($update->message)) {
      $chat_id = $update->message->chat->id;
    } elseif (!is_null($update->edited_message)) {
      $chat_id = $update->edited_message->chat->id;
    } elseif (!is_null($update->channel_post)) {
      $chat_id = $update->channel_post->chat->id;
    } elseif (!is_null($update->callback_query)) {
      if (!is_null($update->callback_query->message)) {
        $chat_id = $update->callback_query->message->chat->id;
      }
    }

    $log = new Log();
    $log->setCreated(new \DateTime('now', new \DateTimeZone('UTC')))
      ->setDirection($direction)
      ->setType($type)
      ->setTelegramChatId($chat_id)
      ->setContent($content);

    $em = $this->getDoctrine()
      ->getManager();
    $em->persist($log);
    $em->flush();
  }

}