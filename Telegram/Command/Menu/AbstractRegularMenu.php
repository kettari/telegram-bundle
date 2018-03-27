<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram\Command\Menu;

use Kettari\TelegramBundle\Exception\MenuException;
use Kettari\TelegramBundle\Exception\TelegramBundleException;
use Kettari\TelegramBundle\Telegram\Communicator;
use Kettari\TelegramBundle\Telegram\TelegramObjectsRetrieverTrait;
use unreal4u\TelegramAPI\Telegram\Types\KeyboardButton;
use unreal4u\TelegramAPI\Telegram\Types\ReplyKeyboardMarkup;
use unreal4u\TelegramAPI\Telegram\Types\Update;

abstract class AbstractRegularMenu extends AbstractMenu
{
  use TelegramObjectsRetrieverTrait;

  /**
   * @inheritDoc
   */
  public function show(Update $update)
  {
    if (empty($this->title)) {
      throw new MenuException('Menu title can\'t be empty.');
    }
    if (is_null(
      $telegramMessage = $this->getMessageFromUpdate($update)
    )) {
      throw new TelegramBundleException(
        'Unable to show menu: Telegram message is not found in the update'
      );
    }

    $this->communicator->sendMessage(
      $telegramMessage->chat->id,
      $this->trans->trans($this->title),
      Communicator::PARSE_MODE_PLAIN,
      $this->getReplyKeyboardMarkup()
    );

    // Mark request handled
    $this->bus->setRequestHandled(true);
  }

  /**
   * Returns reply markup object.
   *
   * @return ReplyKeyboardMarkup
   */
  private function getReplyKeyboardMarkup()
  {
    // Keyboard
    $replyMarkup = new ReplyKeyboardMarkup();
    $replyMarkup->one_time_keyboard = true;
    $replyMarkup->resize_keyboard = true;

    /** @var \Kettari\TelegramBundle\Telegram\Command\Menu\MenuOptionInterface $option */
    $rowIndex = 0;
    $row = [];
    foreach ($this->options as $option) {
      if (!isset($this->layout[$rowIndex])) {
        throw new TelegramBundleException('Layout index exceeded.');
      }

      $button = new KeyboardButton();
      $button->text = $this->trans->trans($option->getCaption());
      $row[] = $button;
      if (count($row) == $this->layout[$rowIndex]) {
        $replyMarkup->keyboard[] = $row;
        $row = [];
        $rowIndex++;
      }
    }

    // Append last row
    if (count($row)) {
      $replyMarkup->keyboard[] = $row;
    }

    return $replyMarkup;
  }

}