<?php

namespace Kettari\TelegramBundle\Telegram;


use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use unreal4u\TelegramAPI\Abstracts\TelegramMethods;
use unreal4u\TelegramAPI\Telegram\Types\Update;

interface BotInterface
{
  /**
   * Adds subscriber to the dispatcher. See services.yml for the list of
   * active subscribers.
   *
   * @param \Symfony\Component\EventDispatcher\EventSubscriberInterface $subscriber
   */
  public function addSubscriber(EventSubscriberInterface $subscriber);

  /**
   * Handles update.
   *
   * May return TelegramMethods object that has to be returned to the Telegram
   * server along with HTTP 200 response. If null is returned than there were
   * several TelegramMethods called and they were sent in separate calls,
   * only HTTP 200 should be returned with empty payload.
   *
   * @param \unreal4u\TelegramAPI\Telegram\Types\Update $update
   * @return null|TelegramMethods
   */
  public function handle(Update $update);
}