<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram\Command;


use Kettari\TelegramBundle\Entity\User;
use Kettari\TelegramBundle\Exception\TelegramBundleException;
use Kettari\TelegramBundle\Telegram\Communicator;
use Kettari\TelegramBundle\Telegram\Event\UserRegisteredEvent;
use unreal4u\TelegramAPI\Telegram\Types\Contact;
use unreal4u\TelegramAPI\Telegram\Types\KeyboardButton;
use unreal4u\TelegramAPI\Telegram\Types\ReplyKeyboardMarkup;
use unreal4u\TelegramAPI\Telegram\Types\ReplyKeyboardRemove;
use unreal4u\TelegramAPI\Telegram\Types\Update;

class RegisterCommand extends AbstractCommand
{

  const BTN_PHONE = 'command.register.send_phone';
  const BTN_CANCEL = 'command.button.cancel';
  const NOTIFICATION_NEW_REGISTER = 'new-register';

  static public $name = 'register';
  static public $description = 'command.register.description';
  static public $requiredPermissions = ['execute command register'];
  static public $declaredNotifications = [self::NOTIFICATION_NEW_REGISTER];

  /**
   * Executes command.
   */
  public function execute()
  {
    // This command is available only in private chat
    if ('private' != $this->update->message->chat->type) {
      $this->replyWithMessage(
        $this->trans->trans('command.private_only')
      );

      return;
    }

    $this->replyWithMessage(
      $this->trans->trans('command.register.send_instruction'),
      Communicator::PARSE_MODE_PLAIN,
      $this->getReplyKeyboardMarkup()
    );

    // Register the hook so when user will send information, we will be notified.
    $this->bus->createHook($this->update, get_class($this), 'handleContact');
  }

  /**
   * Returns reply markup object.
   *
   * @return ReplyKeyboardMarkup
   */
  private function getReplyKeyboardMarkup()
  {
    // Phone button
    $phoneBtn = new KeyboardButton();
    $phoneBtn->request_contact = true;
    $phoneBtn->text = $this->trans->trans(self::BTN_PHONE);

    // Cancel button
    $cancelBtn = new KeyboardButton();
    $cancelBtn->text = $this->trans->trans(self::BTN_CANCEL);

    // Keyboard
    $replyMarkup = new ReplyKeyboardMarkup();
    $replyMarkup->one_time_keyboard = true;
    $replyMarkup->resize_keyboard = true;
    $replyMarkup->keyboard[][] = $phoneBtn;
    $replyMarkup->keyboard[][] = $cancelBtn;

    return $replyMarkup;
  }

  /**
   * Handles update with (possibly) contact from the user.
   */
  public function handleContact()
  {
    $message = $this->update->message;
    if (!is_null($message->contact)) {
      // Check if user sent his contact
      if ($message->contact->user_id != $message->from->id) {
        $this->replyWithMessage(
          $this->trans->trans('command.register.not_your_phone'),
          Communicator::PARSE_MODE_PLAIN,
          new ReplyKeyboardRemove()
        );

        return;
      }

      // Seems to be OK
      if ($this->registerUser($message->contact)) {
        $this->replyWithMessage(
          $this->trans->trans(
            'command.register.success',
            ['%phone_number%' => $message->contact->phone_number]
          )
        );
      }
    } elseif ($this->trans->trans(self::BTN_CANCEL) == $message->text) {
      $this->replyWithMessage(
        $this->trans->trans('command.cancelled'),
        Communicator::PARSE_MODE_PLAIN,
        new ReplyKeyboardRemove()
      );
    } else {

      // If user sent us numbers, help him to understand he should send a contact,
      // not type characters.
      $sanitizedText = $this->sanitizePhone(
        $message->text ? $message->text : ''
      );
      if (mb_strlen($sanitizedText) > 7) {
        $this->replyWithMessage(
          $this->trans->trans('command.register.not_contact'),
          Communicator::PARSE_MODE_HTML,
          $this->getReplyKeyboardMarkup()
        );

        // Register the hook so when user will send information, we will be notified.
        $this->bus->createHook(
          $this->update,
          get_class($this),
          'handleContact'
        );

      } else {
        $this->replyWithMessage(
          $this->trans->trans('command.register.invalid_number'),
          Communicator::PARSE_MODE_PLAIN,
          new ReplyKeyboardRemove()
        );
      }
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
    $telegramUser = $this->update->message->from;

    // Find role object
    /** @var \Kettari\TelegramBundle\Entity\Role $registeredRole */
    $registeredRole = $this->bus->getDoctrine()
      ->getRepository('KettariTelegramBundle:Role')
      ->findOneByName('registered');
    if (is_null($registeredRole)) {
      throw new \LogicException('Role for registered users not found');
    }

    // Find user object. If not found, create new
    /** @var User $userEntity */
    $userEntity = $this->bus->getDoctrine()
      ->getRepository('KettariTelegramBundle:User')
      ->findOneByTelegramId($telegramUser->id);
    if (!$userEntity) {
      throw new TelegramBundleException(
        sprintf(
          'Unable to register user ID=%s: entity expected in the database and not found.',
          $telegramUser->id
        )
      );
    }
    // Update information
    $phone = $this->sanitizePhone(
      $contact->phone_number ? $contact->phone_number : ''
    );
    $userEntity->setPhone($phone);
    if (!$userEntity->hasRole($registeredRole)) {
      $userEntity->addRole($registeredRole);
    }

    // Load default notifications and assign them to the user
    $defaultNotifications = $this->getDefaultNotifications();
    $this->assignDefaultNotifications($userEntity, $defaultNotifications);

    // Commit changes
    $this->bus->getDoctrine()
      ->getManager()
      ->persist($userEntity);
    $this->bus->getDoctrine()
      ->getManager()
      ->flush();

    // Dispatch new registration event
    $this->dispatchNewRegistration($this->update, $userEntity);

    return true;
  }

  /**
   * Sanitize phone and return pure numbers.
   *
   * @param string $phone
   * @return string
   */
  protected function sanitizePhone(string $phone): string
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

    return $needle;
  }

  /**
   * Returns array with notifications for anonymous users.
   *
   * @return array
   */
  private function getDefaultNotifications()
  {
    $notifications = $this->bus->getDoctrine()
      ->getRepository('KettariTelegramBundle:Notification')
      ->findDefault();
    if (0 == count($notifications)) {
      // No error, just no default notifications defined
      return [];
    }

    return $notifications;
  }

  /**
   * Assigns notifications to the User.
   *
   * @param \Kettari\TelegramBundle\Entity\User $user
   * @param array $notifications
   */
  private function assignDefaultNotifications(
    User $user,
    array $notifications
  ) {
    if (count($notifications)) {
      // Load all current notifications assigned to user
      $currentNotifications = $user->getNotifications();
      /** @var \Kettari\TelegramBundle\Entity\Notification $newNotification */
      foreach ($notifications as $newNotification) {
        if (!$currentNotifications->contains($newNotification)) {
          $user->addNotification($newNotification);
          $this->bus->getDoctrine()
            ->getManager()
            ->persist($user);
        }
      }
    }
  }

  /**
   * Dispatches command is unknown.
   *
   * @param Update $update
   * @param \Kettari\TelegramBundle\Entity\User $userEntity
   */
  private function dispatchNewRegistration(
    Update $update,
    User $userEntity
  ) {
    // Dispatch new registration event
    $userRegisteredEvent = new UserRegisteredEvent(
      $update, $userEntity
    );
    $this->bus->getDispatcher()
      ->dispatch(UserRegisteredEvent::NAME, $userRegisteredEvent);
  }

}