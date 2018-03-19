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
    foreach ($this->options as $option) {
      $button = new KeyboardButton();
      $button->text = $this->trans->trans($option->getCaption());
      $replyMarkup->keyboard[][] = $button;
    }

    return $replyMarkup;
  }

}