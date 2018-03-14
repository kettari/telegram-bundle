<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram\Command\Menu;

use Kettari\TelegramBundle\Telegram\Communicator;
use unreal4u\TelegramAPI\Telegram\Types\KeyboardButton;
use unreal4u\TelegramAPI\Telegram\Types\ReplyKeyboardMarkup;

abstract class AbstractRegularMenu extends AbstractMenu
{

  /**
   * @inheritDoc
   */
  public function show(int $chatId)
  {
    $this->bus->getCommunicator()
      ->sendMessage(
        $chatId,
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