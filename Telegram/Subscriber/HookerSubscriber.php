<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram\Subscriber;


use Kettari\TelegramBundle\Entity\Hook;
use Kettari\TelegramBundle\Telegram\CommandBusInterface;
use Kettari\TelegramBundle\Telegram\CommunicatorInterface;
use Kettari\TelegramBundle\Telegram\Event\UpdateReceivedEvent;
use Kettari\TelegramBundle\Telegram\UpdateTypeResolver;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Translation\TranslatorInterface;

class HookerSubscriber extends AbstractBotSubscriber implements EventSubscriberInterface
{
  /**
   * @var \Kettari\TelegramBundle\Telegram\CommandBusInterface
   */
  private $bus;

  /**
   * @var CommunicatorInterface
   */
  private $communicator;

  /**
   * @var TranslatorInterface
   */
  private $trans;

  /**
   * HookerSubscriber constructor.
   *
   * @param \Psr\Log\LoggerInterface $logger
   * @param \Kettari\TelegramBundle\Telegram\CommandBusInterface $bus
   * @param \Kettari\TelegramBundle\Telegram\CommunicatorInterface $communicator
   * @param \Symfony\Component\Translation\TranslatorInterface $translator
   */
  public function __construct(
    LoggerInterface $logger,
    CommandBusInterface $bus,
    CommunicatorInterface $communicator,
    TranslatorInterface $translator
  ) {
    parent::__construct($logger);
    $this->bus = $bus;
    $this->communicator = $communicator;
    $this->trans = $translator;
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
    return [UpdateReceivedEvent::NAME => ['onUpdateReceived', -10000]];
  }

  /**
   * Executes hooks.
   *
   * @param UpdateReceivedEvent $event
   */
  public function onUpdateReceived(UpdateReceivedEvent $event)
  {
    $this->logger->debug(
      'Processing HookerSubscriber::UpdateReceivedEvent',
      [
        'update_id'      => $event->getUpdate()->update_id,
        'callback_query' => $event->getUpdate()->callback_query,
      ]
    );

    // Check for hooks and execute if any found
    /** @var Hook $hook */
    if ($hook = $this->bus->findHook($event->getUpdate())) {

      $this->logger->debug(
        'Found hook ID={hook_id}',
        ['hook_id' => $hook->getId()]
      );

      // Execute & delete the hook
      $this->bus->executeHook($hook, $event->getUpdate())
        ->deleteHook($hook);

    }

    /**
     * Check update type. If it is UT_CALLBACK_QUERY and request is not
     * handled -- we have orphan callback request. Should answer with
     * "Input obsolete, blah-blah"
     */
    if (!$event->getKeeper()
        ->isRequestHandled() && (UpdateTypeResolver::UT_CALLBACK_QUERY ==
        UpdateTypeResolver::getUpdateType($event->getUpdate()))) {

      // Tell the poor guy (girl) to use command again
      $this->communicator->answerCallbackQuery(
        $event->getUpdate()->callback_query->id,
        $this->trans->trans('command.input_obsolete')
      );

    }

    $this->logger->info(
      'HookerSubscriber::UpdateReceivedEvent processed',
      ['update_id' => $event->getUpdate()->update_id]
    );
  }

}