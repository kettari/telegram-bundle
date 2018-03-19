<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram\Subscriber;



use Kettari\TelegramBundle\Telegram\Communicator;
use Kettari\TelegramBundle\Telegram\CommunicatorInterface;
use Kettari\TelegramBundle\Telegram\Event\UpdateReceivedEvent;
use Kettari\TelegramBundle\Telegram\UserHqInterface;
use Psr\Log\LoggerInterface;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Translation\TranslatorInterface;

class UserIdentitySubscriber extends AbstractBotSubscriber implements EventSubscriberInterface
{
  /**
   * @var CommunicatorInterface
   */
  private $communicator;

  /**
   * @var \Symfony\Component\Translation\TranslatorInterface
   */
  private $trans;

  /**
   * @var UserHqInterface
   */
  private $userHq;

  /**
   * UserIdentitySubscriber constructor.
   *
   * @param \Psr\Log\LoggerInterface $logger
   * @param \Kettari\TelegramBundle\Telegram\CommunicatorInterface $communicator
   * @param \Symfony\Component\Translation\TranslatorInterface $translator
   * @param \Kettari\TelegramBundle\Telegram\UserHqInterface $userHq
   */
  public function __construct(
    LoggerInterface $logger,
    CommunicatorInterface $communicator,
    TranslatorInterface $translator,
    UserHqInterface $userHq
  ) {
    parent::__construct($logger);
    $this->communicator = $communicator;
    $this->trans = $translator;
    $this->userHq = $userHq;
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
    return [UpdateReceivedEvent::NAME => ['onUpdateReceived', 80000]];
  }

  /**
   * Resolves telegram user into database object and stores it for the future
   * use.
   *
   * @param UpdateReceivedEvent $event
   */
  public function onUpdateReceived(UpdateReceivedEvent $event)
  {
    $this->logger->debug(
      'Processing UserIdentitySubscriber::UpdateReceivedEvent',
      ['update_id' => $event->getUpdate()->update_id]
    );

    // Resolve current user
    $this->userHq->resolveCurrentUser($event->getUpdate());
    if ($this->userHq->isUserBlocked()) {
      $this->logger->notice('Current user is blocked from our side');

      // Current user is blocked, stop event propagation >:E
      $event->stopPropagation();
      // Then
      if (!is_null($event->getUpdate()->message)) {
        $this->communicator->sendMessage(
            $event->getUpdate()->message->chat->id,
            $this->trans->trans('general.account_blocked'),
            Communicator::PARSE_MODE_PLAIN
          );
      }

    }

    $this->logger->info(
      'UserIdentitySubscriber::UpdateReceivedEvent processed',
      ['update_id' => $event->getUpdate()->update_id]
    );
  }


}