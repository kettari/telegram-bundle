<?php
/**
 * Created by PhpStorm.
 * User: ant
 * Date: 16.03.2017
 * Time: 18:18
 */

namespace Kaula\TelegramBundle\Telegram\Command;


use Kaula\TelegramBundle\Entity\Role;
use Kaula\TelegramBundle\Entity\User;
use unreal4u\TelegramAPI\Telegram\Types\Contact;
use unreal4u\TelegramAPI\Telegram\Types\KeyboardButton;
use unreal4u\TelegramAPI\Telegram\Types\ReplyKeyboardMarkup;

class RegisterCommand extends AbstractUserAwareCommand {

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

    // Register the hook so when user will send information, we will be notified.
    $this->getBus()
      ->getHooker()
      ->createHook($this->getUpdate(), get_class($this), 'handleContact');
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

  /**
   * Handles update with (possibly) contact from the user.
   */
  public function handleContact() {
    $message = $this->getUpdate()->message;
    if (!is_null($message->contact)) {
      // Check if user sent his contact
      if ($message->contact->user_id != $message->from->id) {
        $this->replyWithMessage('Вы прислали не свой номер телефона! Попробуйте ещё раз /register');

        return;
      }

      // Seems to be OK
      if ($this->registerUser($message->contact)) {
        $this->replyWithMessage('Вы зарегистрированы с номером телефона '.
          $message->contact->phone_number);
      }
    }
  }

  /**
   * Registers user in the database.
   *
   * @param \unreal4u\TelegramAPI\Telegram\Types\Contact $contact
   * @return bool
   */
  protected function registerUser(Contact $contact) {
    $tu = $this->getUpdate()->message->from;
    $d = $this->getBus()
      ->getBot()
      ->getContainer()
      ->get('doctrine');
    $em = $d->getManager();

    // Find role object
    $roles = $d->getRepository('KaulaTelegramBundle:Role')
      ->findBy(['registered' => TRUE]);
    if (0 == count($roles)) {
      throw new \LogicException('Roles for registered users not found');
    }

    // Find user object. If not found, create new
    $user = $d->getRepository('KaulaTelegramBundle:User')
      ->find($tu->id);
    if (!$user) {
      $user = new User();
      $user->setId($tu->id)
        ->setFirstName($tu->first_name)
        ->setLastName($tu->last_name)
        ->setUsername($tu->username);
    }
    // Update information
    $user->setPhone($contact->phone_number);
    /** @var Role $single_role */
    foreach ($roles as $single_role) {
      $user->addRole($single_role);
    }

    // Commit changes
    $em->persist($user);
    $em->flush();

    return TRUE;
  }


}