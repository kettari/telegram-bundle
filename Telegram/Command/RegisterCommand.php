<?php
/**
 * Created by PhpStorm.
 * User: ant
 * Date: 16.03.2017
 * Time: 18:18
 */

namespace Kaula\TelegramBundle\Telegram\Command;


use Doctrine\Bundle\DoctrineBundle\Registry;
use Kaula\TelegramBundle\Entity\Role;
use Kaula\TelegramBundle\Entity\User;
use unreal4u\TelegramAPI\Telegram\Types\Contact;
use unreal4u\TelegramAPI\Telegram\Types\KeyboardButton;
use unreal4u\TelegramAPI\Telegram\Types\ReplyKeyboardMarkup;
use unreal4u\TelegramAPI\Telegram\Types\ReplyKeyboardRemove;

class RegisterCommand extends AbstractCommand
{

  static public $name = 'register';
  static public $description = 'Зарегистрироваться у бота';
  static public $required_permissions = ['execute command register'];
  static public $declared_notifications = [self::NOTIFICATION_NEW_REGISTER];

  const BTN_PHONE = 'Сообщить номер телефона';
  const BTN_CANCEL = 'Отмена';

  const NOTIFICATION_NEW_REGISTER = 'new-register';
  const NEW_REGISTER_EMOJI = "\xF0\x9F\x98\x8C";

  /**
   * Executes command.
   */
  public function execute()
  {
    if ('private' == $this->getUpdate()->message->chat->type) {
      $this->replyWithMessage(
        'Чтобы зарегистрироваться, мне нужно узнать ваш телефон.'.PHP_EOL.
        PHP_EOL.'Пришлите мне его, нажав кнопку «Сообщить номер телефона» и подтвердите своё согласие.',
        self::PARSE_MODE_PLAIN,
        $this->getReplyKeyboardMarkup()
      );

      // Register the hook so when user will send information, we will be notified.
      $this->getBus()
        ->getHooker()
        ->createHook($this->getUpdate(), get_class($this), 'handleContact');
    } else {
      $this->replyWithMessage(
        'Эта команда работает только в личной переписке с ботом. В общем канале регистрация невозможна.'
      );
    }
  }

  /**
   * Returns reply markup object.
   *
   * @return ReplyKeyboardMarkup
   */
  private function getReplyKeyboardMarkup()
  {
    // Phone button
    $phone_btn = new KeyboardButton();
    $phone_btn->request_contact = true;
    $phone_btn->text = self::BTN_PHONE;

    // Cancel button
    $cancel_btn = new KeyboardButton();
    $cancel_btn->text = self::BTN_CANCEL;

    // Keyboard
    $reply_markup = new ReplyKeyboardMarkup();
    $reply_markup->one_time_keyboard = true;
    $reply_markup->resize_keyboard = true;
    $reply_markup->keyboard[][] = $phone_btn;
    $reply_markup->keyboard[][] = $cancel_btn;

    return $reply_markup;
  }

  /**
   * Handles update with (possibly) contact from the user.
   */
  public function handleContact()
  {
    $message = $this->getUpdate()->message;
    if (!is_null($message->contact)) {
      // Check if user sent his contact
      if ($message->contact->user_id != $message->from->id) {
        $this->replyWithMessage(
          'Вы прислали не свой номер телефона! Попробуйте ещё раз /register',
          '',
          new ReplyKeyboardRemove()
        );

        return;
      }

      // Seems to be OK
      if ($this->registerUser($message->contact)) {
        $this->replyWithMessage(
          'Вы зарегистрированы с номером телефона '.
          $message->contact->phone_number.PHP_EOL.PHP_EOL.
          'Теперь у вас есть доступ к командам для зарегистрированных пользователей, проверьте их список по команде /help'
        );
      }
    } elseif (self::BTN_CANCEL == $message->text) {
      $this->replyWithMessage(
        'Регистрация отменена.',
        '',
        new ReplyKeyboardRemove()
      );
    } else {
      $this->replyWithMessage(
        'Вы прислали не телефон, а что-то, мне непонятное. Попробуйте ещё раз команду /register',
        '',
        new ReplyKeyboardRemove()
      );
    }
  }

  /**
   * Registers user in the database.
   *
   * @param \unreal4u\TelegramAPI\Telegram\Types\Contact $contact
   * @return bool
   */
  protected function registerUser(Contact $contact)
  {
    $tu = $this->getUpdate()->message->from;
    $d = $this->getBus()
      ->getBot()
      ->getContainer()
      ->get('doctrine');
    $em = $d->getManager();

    // Find role object
    $roles = $d->getRepository('KaulaTelegramBundle:Role')
      ->findBy(['name' => 'registered']);
    if (0 == count($roles)) {
      throw new \LogicException('Roles for registered users not found');
    }

    // Find user object. If not found, create new
    $user = $d->getRepository('KaulaTelegramBundle:User')
      ->findOneBy(['telegram_id' => $tu->id]);
    if (!$user) {
      $user = new User();
      $user->setTelegramId($tu->id)
        ->setFirstName($tu->first_name)
        ->setLastName($tu->last_name)
        ->setUsername($tu->username);
    }
    // Update information
    $phone = $this->sanitizePhone($contact->phone_number);
    $user->setPhone($phone);
    /** @var Role $single_role */
    foreach ($roles as $single_role) {
      $user->addRole($single_role);
    }

    // Load default notifications and assign them to the user
    $default_notifications = $this->getDefaultNotifications($d);
    $this->assignDefaultNotifications($d, $user, $default_notifications);

    // Commit changes
    $em->persist($user);
    $em->flush();

    // Push info about new registration
    $this->getBus()
      ->getBot()
      ->pushNotification(
        self::NOTIFICATION_NEW_REGISTER,
        sprintf(
          '%s Новая регистрация: %s (#%s)',
          self::NEW_REGISTER_EMOJI,
          trim($user->getLastName().' '.$user->getFirstName()),
          $user->getTelegramId()
        )
      );
    $this->getBus()
      ->getBot()
      ->bumpQueue();

    return true;
  }

  /**
   * Sanitize phone and return pure numbers.
   *
   * @param string $phone
   * @return mixed
   */
  protected function sanitizePhone($phone)
  {
    // Remove all chars except numbers
    $needle = preg_replace('/[^0-9]/', '', $phone);
    // Replace leading 8 with 7
    if ('8' == substr($needle, 0, 1)) {
      $needle = '7'.substr($needle, 1);
    }
    // Add missing digit
    if (strlen($needle) == 10) {
      $needle = '7'.$needle;
    }

    return empty($needle) ? null : $needle;
  }

  /**
   * Returns array with notifications for anonymous users.
   *
   * @param \Doctrine\Bundle\DoctrineBundle\Registry $d
   * @return array
   */
  private function getDefaultNotifications(Registry $d)
  {
    $notifications = $d->getRepository('KaulaTelegramBundle:Notification')
      ->findBy(['user_default' => true]);
    if (0 == count($notifications)) {
      // No error, just no default notifications defined
      return [];
    }

    return $notifications;
  }

  /**
   * Assigns notifications to the User.
   *
   * @param \Doctrine\Bundle\DoctrineBundle\Registry $d
   * @param \Kaula\TelegramBundle\Entity\User $user
   * @param array $notifications
   */
  private function assignDefaultNotifications(
    Registry $d,
    User $user,
    $notifications
  ) {
    if (count($notifications)) {
      $em = $d->getManager();

      // Load all current notifications assigned to user
      $current_notifications = $user->getNotifications();
      /** @var \Kaula\TelegramBundle\Entity\Notification $new_notification */
      foreach ($notifications as $new_notification) {
        if (!$current_notifications->contains($new_notification)) {
          $user->addNotification($new_notification);
          $em->persist($user);
        }
      }
    }
  }

}