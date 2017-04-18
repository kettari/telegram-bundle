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
use Kaula\TelegramBundle\Repository\UserRepository;
use unreal4u\TelegramAPI\Telegram\Types\KeyboardButton;
use unreal4u\TelegramAPI\Telegram\Types\ReplyKeyboardMarkup;
use unreal4u\TelegramAPI\Telegram\Types\ReplyKeyboardRemove;

class UserManCommand extends AbstractCommand
{

  static public $name = 'userman';
  static public $description = 'Управление ролями пользователей';
  static public $required_permissions = ['execute command userman'];

  const LIST_MARK = "\xF0\x9F\x94\xB9";

  const BTN_ROLES_ADD = 'Добавить роли';
  const BTN_ROLES_REMOVE = 'Убрать роли';
  const BTN_BLOCK = 'Изменить блокировку';
  const BTN_CANCEL = 'Отмена';

  /**
   * Executes command.
   */
  public function execute()
  {
    $parameter = $this->getParameter();
    if (!empty($parameter)) {
      $this->showUserManMenu($parameter);

      return;
    }

    $this->replyWithMessage(
      'Укажите часть ФИО, никнейма либо номер телефона:',
      null,
      $this->getReplyKeyboardMarkup_Cancel()
    );

    // Create the hook to handle user's reply
    $this->getBus()
      ->getHooker()
      ->createHook(
        $this->getUpdate(),
        get_class($this),
        'showUserManMenu'
      );
  }

  /**
   * Returns reply markup object.
   *
   * @return ReplyKeyboardMarkup
   */
  private function getReplyKeyboardMarkup_Cancel()
  {
    // Cancel button
    $cancel_btn = new KeyboardButton();
    $cancel_btn->text = self::BTN_CANCEL;

    // Keyboard
    $reply_markup = new ReplyKeyboardMarkup();
    $reply_markup->one_time_keyboard = true;
    $reply_markup->resize_keyboard = true;
    $reply_markup->keyboard[][] = $cancel_btn;

    return $reply_markup;
  }

  /**
   * Handles /userman user credentials request.
   *
   * @param string $inline_name
   */
  public function showUserManMenu($inline_name = '')
  {
    if (empty($inline_name)) {
      $name = trim($this->getUpdate()->message->text);
    } else {
      $name = $inline_name;
    }
    // Simple checks
    if (empty($name)) {
      return;
    }
    if (strlen($name) < 3) {
      $this->replyWithMessage('Запрос должен содержать минимум 3 символа.');

      return;
    }

    // Cancel
    if (self::BTN_CANCEL == $name) {
      $this->replyWithMessage(
        'Команда отменена.',
        null,
        new ReplyKeyboardRemove()
      );

      return;
    }

    $d = $this->getBus()
      ->getBot()
      ->getContainer()
      ->get('doctrine');

    /** @var UserRepository $r */
    $r = $d->getRepository('KaulaTelegramBundle:User');
    $users = $r->findAnyone($name);
    if (0 == count($users)) {
      $this->replyWithMessage(
        'Не нашёл ни одного пользователя, удовлетворяющего этим критериями.',
        null,
        new ReplyKeyboardRemove()
      );

      return;
    } elseif (count($users) > 1) {
      $this->replyWithMessage(
        sprintf(
          'Нашёл нескольких пользователей (%d, если быть точным), удовлетворяющих этим критериями. Пожалуйста, уточните запрос так, чтобы был найден только один.',
          count($users)
        ),
        null,
        new ReplyKeyboardRemove()
      );

      return;
    }

    /** @var \Kaula\TelegramBundle\Entity\User $found_user */
    $found_user = reset($users);
    $this->replyWithMessage(
      $this->getUserInformation($found_user),
      self::PARSE_MODE_HTML,
      $this->getReplyKeyboardMarkup_MainMenu()
    );

    $this->getBus()
      ->getHooker()
      ->createHook(
        $this->getUpdate(),
        get_class($this),
        'handleUserMan_Menu',
        serialize($found_user)
      );
  }

  /**
   * Returns string with information about user's roles, block status etc.
   *
   * @param User $user
   * @return string
   */
  private function getUserInformation($user) {
    $text = sprintf(
        '<b>%s</b>',
        trim($user->getFirstName().' '.$user->getLastName())
      ).PHP_EOL;
    $text .= 'В Талланто: '.trim(
        $user->getExternalFirstName().' '.
        $user->getExternalLastName()
      ).PHP_EOL;
    $text .= 'Телефон: '.$user->getPhone().PHP_EOL;
    $text .= 'Telegram ID: '.$user->getTelegramId().PHP_EOL;
    $text .= 'Заблокирован: '.($user->isBlocked() ? 'Да' : 'Нет').PHP_EOL;
    // Enumerate roles
    $text .= PHP_EOL.'<b>Роли:</b>'.PHP_EOL;
    /** @var \Kaula\TelegramBundle\Entity\Role $role */
    foreach ($user->getRoles() as $role) {
      $text .= self::LIST_MARK.' '.$role->getName().PHP_EOL;
    }

    return $text;
  }

  /**
   * Returns reply markup object.
   *
   * @return \unreal4u\TelegramAPI\Telegram\Types\ReplyKeyboardMarkup
   */
  private function getReplyKeyboardMarkup_MainMenu()
  {
    // Keyboard
    $reply_markup = new ReplyKeyboardMarkup();
    $reply_markup->one_time_keyboard = true;
    $reply_markup->resize_keyboard = true;

    // Roles
    $roles_btn = new KeyboardButton();
    $roles_btn->text = self::BTN_ROLES_ADD;
    $reply_markup->keyboard[][] = $roles_btn;
    $roles_btn = new KeyboardButton();
    $roles_btn->text = self::BTN_ROLES_REMOVE;
    $reply_markup->keyboard[][] = $roles_btn;
    // Block
    $block_btn = new KeyboardButton();
    $block_btn->text = self::BTN_BLOCK;
    $reply_markup->keyboard[][] = $block_btn;
    // Cancel button
    $cancel_btn = new KeyboardButton();
    $cancel_btn->text = self::BTN_CANCEL;
    $reply_markup->keyboard[][] = $cancel_btn;

    return $reply_markup;
  }

  /**
   * Handles /userman main menu.
   *
   * @param mixed $serialized_found_user
   */
  public function handleUserMan_Menu($serialized_found_user)
  {
    /** @var \Kaula\TelegramBundle\Entity\User $found_user */
    $found_user = unserialize($serialized_found_user);
    // Selection
    $selection = trim($this->getUpdate()->message->text);
    // Simple checks
    if (empty($selection)) {
      return;
    }

    // Cancel
    if (self::BTN_CANCEL == $selection) {
      $this->replyWithMessage(
        'Команда отменена.',
        null,
        new ReplyKeyboardRemove()
      );

      return;
    }

    // Add roles option
    if (self::BTN_ROLES_ADD == $selection) {
      $this->replyWithMessage(
        'Какую роль добавить пользователю?',
        self::PARSE_MODE_HTML,
        $this->getReplyKeyboardMarkup_Roles_Add($found_user)
      );

      $this->getBus()
        ->getHooker()
        ->createHook(
          $this->getUpdate(),
          get_class($this),
          'handleUserManRoleAdd',
          $serialized_found_user
        );

      return;
    }

    // Remove roles option
    if (self::BTN_ROLES_REMOVE == $selection) {
      $this->replyWithMessage(
        'Какую роль убрать у пользователя?',
        self::PARSE_MODE_HTML,
        $this->getReplyKeyboardMarkup_Roles_Remove($found_user)
      );

      $this->getBus()
        ->getHooker()
        ->createHook(
          $this->getUpdate(),
          get_class($this),
          'handleUserManRoleRemove',
          $serialized_found_user
        );

      return;
    }

    // Change blocked status
    if (self::BTN_BLOCK == $selection) {
      $this->changeBlockState($found_user);

      return;
    }

    $this->replyWithMessage(
      'Команда непонятна. Попробуйте еще раз /userman',
      null,
      new ReplyKeyboardRemove()
    );
  }

  /**
   * Returns reply markup object for Roles -> Add
   *
   * @param User $found_user
   * @return \unreal4u\TelegramAPI\Telegram\Types\ReplyKeyboardMarkup
   */
  private function getReplyKeyboardMarkup_Roles_Add($found_user)
  {
    // Keyboard
    $reply_markup = new ReplyKeyboardMarkup();
    $reply_markup->one_time_keyboard = true;
    $reply_markup->resize_keyboard = true;

    $d = $this->getBus()
      ->getBot()
      ->getContainer()
      ->get('doctrine');

    /** @var User $user_to_manipulate */
    if (is_null(
      $user_to_manipulate = $d->getRepository('KaulaTelegramBundle:User')
        ->find($found_user->getId())
    )) {
      $this->replyWithMessage(
        'Пользователь не найден :('
      );

      return null;
    }

    /** @var \Kaula\TelegramBundle\Entity\Role $role */
    foreach ($d->getRepository('KaulaTelegramBundle:Role')
               ->findBy(
                 [
                   'administrator' => false,
                   'anonymous'     => false,
                 ]
               ) as $role) {
      if ($user_to_manipulate->getRoles()
        ->contains($role)
      ) {
        continue;
      }
      $role_btn = new KeyboardButton();
      $role_btn->text = $role->getName();
      $reply_markup->keyboard[][] = $role_btn;
    }
    // Cancel button
    $cancel_btn = new KeyboardButton();
    $cancel_btn->text = self::BTN_CANCEL;
    $reply_markup->keyboard[][] = $cancel_btn;

    return $reply_markup;
  }

  /**
   * Returns reply markup object for Roles -> Remove
   *
   * @param User $found_user
   * @return \unreal4u\TelegramAPI\Telegram\Types\ReplyKeyboardMarkup
   */
  private function getReplyKeyboardMarkup_Roles_Remove($found_user)
  {
    // Keyboard
    $reply_markup = new ReplyKeyboardMarkup();
    $reply_markup->one_time_keyboard = true;
    $reply_markup->resize_keyboard = true;

    /** @var \Kaula\TelegramBundle\Entity\Role $role */
    foreach ($found_user->getRoles() as $role) {
      // Safe check
      if ($role->getAnonymous() || $role->getAdministrator()) {
        continue;
      }
      $role_btn = new KeyboardButton();
      $role_btn->text = $role->getName();
      $reply_markup->keyboard[][] = $role_btn;
    }
    // Cancel button
    $cancel_btn = new KeyboardButton();
    $cancel_btn->text = self::BTN_CANCEL;
    $reply_markup->keyboard[][] = $cancel_btn;

    return $reply_markup;
  }

  /**
   * Handles /userman add role to the user.
   *
   * @param mixed $serialized_found_user
   */
  public function handleUserManRoleAdd($serialized_found_user)
  {
    /** @var \Kaula\TelegramBundle\Entity\User $found_user */
    $found_user = unserialize($serialized_found_user);
    // Selection
    $selection = trim($this->getUpdate()->message->text);

    // Cancel
    if (self::BTN_CANCEL == $selection) {
      $this->replyWithMessage(
        'Команда отменена.',
        null,
        new ReplyKeyboardRemove()
      );

      return;
    }

    $d = $this->getBus()
      ->getBot()
      ->getContainer()
      ->get('doctrine');

    if (is_null(
      $role_to_add = $d->getRepository('KaulaTelegramBundle:Role')
        ->findOneBy(['name' => $selection])
    )) {
      $this->replyWithMessage(
        'Роль не найдена в списке: '.$selection
      );

      return;
    }

    /** @var User $user_to_manipulate */
    if (is_null(
      $user_to_manipulate = $d->getRepository('KaulaTelegramBundle:User')
        ->find($found_user->getId())
    )) {
      $this->replyWithMessage(
        'Пользователь не найден :('
      );

      return;
    }

    // Add the role
    $user_to_manipulate->addRole($role_to_add);
    $d->getManager()
      ->flush();

    $this->replyWithMessage('Роль добавлена.');
    $this->replyWithMessage($this->getUserInformation($user_to_manipulate), self::PARSE_MODE_HTML);
  }

  /**
   * Handles /userman remove role from the user
   *
   * @param mixed $serialized_found_user
   */
  public function handleUserManRoleRemove($serialized_found_user)
  {
    /** @var \Kaula\TelegramBundle\Entity\User $found_user */
    $found_user = unserialize($serialized_found_user);
    // Selection
    $selection = trim($this->getUpdate()->message->text);

    // Cancel
    if (self::BTN_CANCEL == $selection) {
      $this->replyWithMessage(
        'Команда отменена.',
        null,
        new ReplyKeyboardRemove()
      );

      return;
    }

    $d = $this->getBus()
      ->getBot()
      ->getContainer()
      ->get('doctrine');

    /** @var Role $role_to_remove */
    if (is_null(
      $role_to_remove = $d->getRepository('KaulaTelegramBundle:Role')
        ->findOneBy(['name' => $selection])
    )) {
      $this->replyWithMessage(
        'Роль не найдена в списке: '.$selection
      );

      return;
    }

    /** @var User $user_to_manipulate */
    if (is_null(
      $user_to_manipulate = $d->getRepository('KaulaTelegramBundle:User')
        ->find($found_user->getId())
    )) {
      $this->replyWithMessage(
        'Пользователь не найден :('
      );

      return;
    }

    // Remove notifications that are bound to the role going to be removed
    /** @var \Kaula\TelegramBundle\Entity\Notification $notification */
    foreach ($user_to_manipulate->getNotifications() as $notification) {
      if ($role_to_remove->getPermissions()
        ->contains($notification->getPermission())
      ) {
        $user_to_manipulate->removeNotification($notification);
      }
    }
    // Remove the role
    $user_to_manipulate->removeRole($role_to_remove);
    $d->getManager()
      ->flush();

    $this->replyWithMessage('Роль убрана.');
    $this->replyWithMessage($this->getUserInformation($user_to_manipulate), self::PARSE_MODE_HTML);
  }

  /**
   * Change state of the user.
   *
   * @param User $found_user
   */
  public function changeBlockState($found_user)
  {
    $d = $this->getBus()
      ->getBot()
      ->getContainer()
      ->get('doctrine');

    /** @var User $user_to_manipulate */
    if (is_null(
      $user_to_manipulate = $d->getRepository('KaulaTelegramBundle:User')
        ->find($found_user->getId())
    )) {
      $this->replyWithMessage(
        'Пользователь не найден :('
      );

      return;
    }

    // Change state of block
    //$state = ;
    $user_to_manipulate->setBlocked(!$user_to_manipulate->isBlocked());
    $d->getManager()
      ->flush();

    $this->replyWithMessage('Статус блокровки изменён.');
    $this->replyWithMessage($this->getUserInformation($user_to_manipulate), self::PARSE_MODE_HTML);
  }

}