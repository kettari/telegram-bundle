<?php
/**
 * Created by PhpStorm.
 * User: ant
 * Date: 25.04.2017
 * Time: 13:15
 */

namespace Kettari\TelegramBundle\Telegram\Event;


use RuntimeException;
use unreal4u\TelegramAPI\Telegram\Types\Invoice;
use unreal4u\TelegramAPI\Telegram\Types\Update;

class PaymentInvoiceEvent extends AbstractMessageEvent
{
  const NAME = 'telegram.payment.invoice';

  /**
   * PaymentInvoiceEvent constructor.
   *
   * @param Update $update
   */
  public function __construct(Update $update)
  {
    if (is_null($update->message)) {
      throw new RuntimeException(
        'Message can\'t be null for the PaymentInvoiceEvent.'
      );
    }
    if (empty($update->message->invoice)) {
      throw new RuntimeException(
        'Invoice of the Message can\'t be empty for the PaymentInvoiceEvent.'
      );
    }

    $this->setMessage($update->message)
      ->setUpdate($update);
  }

  /**
   * Message is an invoice for a payment, information about the invoice
   *
   * @return Invoice
   */
  public function getInvoice(): Invoice
  {
    return $this->getMessage()->invoice;
  }

}