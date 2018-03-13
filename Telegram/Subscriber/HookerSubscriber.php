<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram\Subscriber;


use Kettari\TelegramBundle\Entity\Hook;
use Kettari\TelegramBundle\Telegram\Event\UpdateReceivedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use unreal4u\TelegramAPI\Telegram\Types\Update;

class HookerSubscriber extends AbstractBotSubscriber implements EventSubscriberInterface
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
    return [UpdateReceivedEvent::NAME => ['onUpdateReceived', 70000]];
  }

  /**
   * Executes hooks.
   *
   * @param UpdateReceivedEvent $event
   */
  public function onUpdateReceived(UpdateReceivedEvent $event)
  {
    $this->executeHook($event->getUpdate());
  }

  /**
   * Finds the hook and executes it.
   *
   * @param Update $update
   */
  private function executeHook(Update $update)
  {
    // Check for hooks and execute if any found
    /** @var Hook $hook */
    if ($hook = $this->bus->findHook($update)) {

      // Set flag that request is handled
      /*$this->getBot()
        ->setRequestHandled(true);*/
      // Execute & delete the hook
      $this->bus->executeHook($hook, $update)
        ->deleteHook($hook);

    }
  }

}