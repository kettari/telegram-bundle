<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram\Event;


use RuntimeException;
use unreal4u\TelegramAPI\Telegram\Types\SuccessfulPayment;
use unreal4u\TelegramAPI\Telegram\Types\Update;

class PaymentSuccessfulEvent extends AbstractMessageEvent
{
  const NAME = 'telegram.payment.successful';

  /**
   * PaymentSuccessfulEvent constructor.
   *
   * @param Update $update
   */
  public function __construct(Update $update)
  {
    parent::__construct($update);
    if (empty($update->message->successful_payment)) {
      throw new RuntimeException(
        'Successful payment of the Message can\'t be empty for the PaymentSuccessfulEvent.'
      );
    }
  }

  /**
   * Message is a service message about a successful payment, information about
   * the payment
   *
   * @return SuccessfulPayment
   */
  public function getSuccessfulPayment(): SuccessfulPayment
  {
    return $this->getMessage()->successful_payment;
  }

}