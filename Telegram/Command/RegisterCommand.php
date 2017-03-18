<?php
/**
 * Created by PhpStorm.
 * User: ant
 * Date: 16.03.2017
 * Time: 18:18
 */

namespace Kaula\TelegramBundle\Telegram\Command;




use unreal4u\TelegramAPI\Telegram\Types\KeyboardButton;
use unreal4u\TelegramAPI\Telegram\Types\ReplyKeyboardMarkup;

class RegisterCommand extends AbstractCommand {

  static public $name = 'register';
  static public $description = 'Зарегистрироваться у бота';

  /**
   * Executes command.
   */
  public function execute() {
    $this->replyWithMessage('Чтобы зарегистрироваться, мне нужно узнать ваш телефон.'.
      PHP_EOL.PHP_EOL.
      'Пришлите его мне с помощью кнопки «Сообщить номер телефона».',
      self::PARSE_MODE_PLAIN, $this->getReplyKeyboardMarkup());

    $this->getBus()->createHook($this->update, get_class($this), 'handleContact');
  }

  /**
   * Returns reply markup object.
   *
   * @return ReplyKeyboardMarkup
   */
  private function getReplyKeyboardMarkup() {
    // Phone button
    $phone_btn = new KeyboardButton();
    $phone_btn->request_contact = TRUE;
    $phone_btn->text = 'Сообщить номер телефона';

    // Cancel button
    $cancel_btn = new KeyboardButton();
    $cancel_btn->text = 'Отмена';

    // Keyboard
    $reply_markup = new ReplyKeyboardMarkup();
    $reply_markup->one_time_keyboard = TRUE;
    $reply_markup->resize_keyboard = TRUE;
    $reply_markup->keyboard[][] = $phone_btn;
    $reply_markup->keyboard[][] = $cancel_btn;

    return $reply_markup;
  }

  public function handleContact() {
    $this->replyWithMessage('Получил. Кажется контакт');
  }


}