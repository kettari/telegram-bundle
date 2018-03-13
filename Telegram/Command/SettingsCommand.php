<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram\Command;


use unreal4u\TelegramAPI\Telegram\Types\Inline\Keyboard\Button;
use unreal4u\TelegramAPI\Telegram\Types\Inline\Keyboard\Markup;
use unreal4u\TelegramAPI\Telegram\Types\KeyboardButton;
use unreal4u\TelegramAPI\Telegram\Types\ReplyKeyboardMarkup;
use unreal4u\TelegramAPI\Telegram\Types\ReplyKeyboardRemove;

class SettingsCommand extends AbstractCommand
{

  const BTN_NOTIFICATION = 'Уведомления';
  const BTN_CANCEL = 'Ничего не менять';
  const MARK_V = "\xE2\x9C\x85";
  const MARK_X = "\xE2\x9D\x8C";
  static public $name = 'settings';
  static public $description = 'Настройки бота';
  static public $requiredPermissions = ['execute command settings'];

  /**
   * Executes command.
   */
  public function execute()
  {
    if ('private' == $this->update->message->chat->type) {
      $this->replyWithMessage(
        'Какие настройки бота вы хотите изменить?',
        '',
        $this->getReplyKeyboardMarkup_MainMenu()
      );

      $this->bus->createHook(
        $this->update,
        get_class($this),
        'handleSettingsMainMenu'
      );
    } else {
      $this->replyWithMessage(
        'Эта команда работает только в личной переписке с ботом. В общем канале управление настройками невозможно.'
      );
    }
  }

  /**
   * Returns reply markup object.
   *
   * @return ReplyKeyboardMarkup
   */
  private function getReplyKeyboardMarkup_MainMenu()
  {
    // Notification button
    $notificationBtn = new KeyboardButton();
    $notificationBtn->text = self::BTN_NOTIFICATION;

    // Cancel button
    $cancelBtn = new KeyboardButton();
    $cancelBtn->text = self::BTN_CANCEL;

    // Keyboard
    $replyMarkup = new ReplyKeyboardMarkup();
    $replyMarkup->one_time_keyboard = true;
    $replyMarkup->resize_keyboard = true;
    $replyMarkup->keyboard[][] = $notificationBtn;
    $replyMarkup->keyboard[][] = $cancelBtn;

    return $replyMarkup;
  }

  /**
   * Handles /settings main menu.
   */
  public function handleSettingsMainMenu()
  {
    $message = $this->update->message;
    if (is_null($message)) {
      return;
    }

    switch ($message->text) {
      case self::BTN_NOTIFICATION:
        $this->replyWithMessage(
          'Отметьте уведомления, которые хотите получать:',
          '',
          $this->getReplyKeyboardMarkup_Notifications()
        );
        $this->bus->createHook(
          $this->update,
          get_class($this),
          'handleSettingsNotificationOption'
        );
        break;
      case self::BTN_CANCEL:
        $this->replyWithMessage(
          'Команда отменена.',
          '',
          new ReplyKeyboardRemove()
        );
        break;
      default:
        $this->replyWithMessage(
          'Вы прислали непонятный мне вариант меню. Попробуйте ещё раз /settings',
          '',
          new ReplyKeyboardRemove()
        );
        break;
    }
  }

  /**
   * Returns reply markup for notifications option.
   *
   * @return \unreal4u\TelegramAPI\Telegram\Types\Inline\Keyboard\Markup
   */
  private function getReplyKeyboardMarkup_Notifications()
  {
    // Load user's permissions and notifications
    $userPermissions = $this->bus->getUserHq()
      ->getUserPermissions();
    $userNotifications = $this->bus->getUserHq()
      ->getUserNotifications();

    // Load notifications
    /** @var \Kettari\TelegramBundle\Entity\Notification $notifications */
    $notifications = $this->bus->getDoctrine()
      ->getRepository('KettariTelegramBundle:Notification')
      ->findAllOrdered();
    // Check if user has required for each notification permission
    $inlineKeyboard = new Markup();
    /** @var \Kettari\TelegramBundle\Entity\Notification $notificationItem */
    foreach ($notifications as $notificationItem) {
      $row = [];
      if ($userPermissions->contains($notificationItem->getPermission())) {

        // Does user have this notification enabled?
        $mark = ($userNotifications->contains(
          $notificationItem
        )) ? self::MARK_V : self::MARK_X;

        $inlineKeyboardButton = new Button();
        $inlineKeyboardButton->text = $mark.' '.$notificationItem->getTitle();
        $inlineKeyboardButton->callback_data = $notificationItem->getName();
        $row[] = $inlineKeyboardButton;
      }
      if (count($row)) {
        $inlineKeyboard->inline_keyboard[] = $row;
      }
    }

    return $inlineKeyboard;
  }

  /**
   * Handles selection of the Notification option in the Settings
   */
  public function handleSettingsNotificationOption()
  {
    $cq = $this->update->callback_query;
    if (is_null($cq) && !is_null($this->update->message)) {
      $this->replyWithMessage('Команда отменена.');

      return;
    }
    if (is_null($cq) || is_null($cq->message)) {
      return;
    }

    // Load user's permissions and notifications
    /** @var \Kettari\TelegramBundle\Entity\User $user */
    $user = $this->bus->getUserHq()
      ->getCurrentUser();
    $userPermissions = $this->bus->getUserHq()
      ->getUserPermissions();
    $userNotifications = $this->bus->getUserHq()
      ->getUserNotifications();

    // Load notification
    /** @var \Kettari\TelegramBundle\Entity\Notification $notification */
    $notification = $this->bus->getDoctrine()
      ->getRepository('KettariTelegramBundle:Notification')
      ->findOneByName($cq->data);
    if (is_null($notification)) {
      return;
    }
    // Check user permission
    if (!$userPermissions->contains($notification->getPermission())) {
      return;
    }

    // Enable notification or disable it
    if ($userNotifications->contains($notification)) {
      $user->removeNotification($notification);
    } else {
      $user->addNotification($notification);
    }
    // Save changes
    $this->bus->getDoctrine()
      ->getManager()
      ->persist($user);
    $this->bus->getDoctrine()
      ->getManager()
      ->flush();

    // Update keyboard
    $this->bus->getCommunicator()
      ->editMessageReplyMarkup(
        $cq->message->chat->id,
        $cq->message->message_id,
        null,
        $this->getReplyKeyboardMarkup_Notifications()
      );
    // Answer callback
    $this->bus->getCommunicator()
      ->answerCallbackQuery($cq->id, 'Настройки уведомлений изменены.');
    // Register this hook again
    $this->bus->createHook(
        $this->update,
        get_class($this),
        'handleSettingsNotificationOption'
      );
  }

}