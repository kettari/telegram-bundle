<?php
declare(strict_types=1);

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
    parent::__construct($update);
    if (empty($update->message->invoice)) {
      throw new RuntimeException(
        'Invoice of the Message can\'t be empty for the PaymentInvoiceEvent.'
      );
    }
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