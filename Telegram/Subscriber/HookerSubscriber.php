<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram\Subscriber;


use Kettari\TelegramBundle\Entity\Hook;
use Kettari\TelegramBundle\Telegram\Event\UpdateReceivedEvent;
use Kettari\TelegramBundle\Telegram\UpdateTypeResolver;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

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
    $this->logger->debug(
      'Processing HookerSubscriber::UpdateReceivedEvent for the update ID={update_id}',
      ['update_id' => $event->getUpdate()->update_id]
    );

    // Check for hooks and execute if any found
    /** @var Hook $hook */
    if ($hook = $this->bus->findHook($event->getUpdate())) {

      $this->logger->debug(
        'Found hook ID={hook_id}',
        ['hook_id' => $hook->getId()]
      );

      // Set flag that request is handled
      $event->getKeeper()
        ->setRequestHandled(true);
      // Execute & delete the hook
      $this->bus->executeHook($hook, $event->getUpdate())
        ->deleteHook($hook);

    } else {
      /**
       * No hooks found. Check update type. If it is UT_CALLBACK_QUERY then
       * we have orphan request. Should answer with "Input obsolete, blah-blah"
       */
      if (UpdateTypeResolver::UT_CALLBACK_QUERY ==
        UpdateTypeResolver::getUpdateType($event->getUpdate())) {

        // Tell the poor guy (girl) to use command again
        $this->communicator->answerCallbackQuery(
          $event->getUpdate()->callback_query->id,
          $this->bus->getTrans()
            ->trans('command.input_obsolete')
        );

      }
    }

    $this->logger->info(
      'HookerSubscriber::UpdateReceivedEvent for the update ID={update_id} processed',
      ['update_id' => $event->getUpdate()->update_id]
    );
  }

}