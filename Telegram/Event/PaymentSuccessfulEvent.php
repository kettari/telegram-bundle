<?php
/**
 * Created by PhpStorm.
 * User: ant
 * Date: 25.04.2017
 * Time: 13:15
 */

namespace Kaula\TelegramBundle\Telegram\Event;


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
    if (is_null($update->message)) {
      throw new RuntimeException(
        'Message can\'t be null for the PaymentSuccessfulEvent.'
      );
    }
    if (empty($update->message->successful_payment)) {
      throw new RuntimeException(
        'Successful payment of the Message can\'t be empty for the PaymentSuccessfulEvent.'
      );
    }

    $this->setMessage($update->message)
      ->setUpdate($update);
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