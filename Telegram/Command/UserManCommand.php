<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram\Command;


use Kettari\TelegramBundle\Entity\Role;
use Kettari\TelegramBundle\Entity\User;
use Kettari\TelegramBundle\Telegram\Communicator;
use unreal4u\TelegramAPI\Telegram\Types\KeyboardButton;
use unreal4u\TelegramAPI\Telegram\Types\ReplyKeyboardMarkup;
use unreal4u\TelegramAPI\Telegram\Types\ReplyKeyboardRemove;

class UserManCommand extends AbstractCommand
{

  const LIST_MARK = "\xF0\x9F\x94\xB9";
  const BTN_ROLES_ADD = 'Добавить роли';
  const BTN_ROLES_REMOVE = 'Убрать роли';
  const BTN_BLOCK = 'Изменить блокировку';
  const BTN_CANCEL = 'Отмена';
  static public $name = 'userman';
  static public $description = 'Управление ролями пользователей';
  static public $requiredPermissions = ['execute command userman'];

  /**
   * Executes command.
   *
   * @throws \Exception
   */
  public function execute()
  {
    if ('private' == $this->update->message->chat->type) {
      if (!empty($this->getCommandParameter())) {
        $this->showUserManMenu($this->getCommandParameter());

        return;
      }

      $this->replyWithMessage(
        'Укажите часть ФИО, никнейма либо номер телефона:',
        null,
        $this->getReplyKeyboardMarkup_Cancel()
      );

      // Create the hook to handle user's reply
      $this->bus->createHook(
        $this->update,
        get_class($this),
        'showUserManMenu'
      );
    } else {
      $this->replyWithMessage(
        'Эта команда работает только в личной переписке с ботом. В общем канале управление пользователями невозможно.'
      );
    }
  }

  /**
   * Handles /userman user credentials request.
   *
   * @param string $inlineName
   * @throws \Exception
   */
  public function showUserManMenu($inlineName = '')
  {
    if (empty($inlineName)) {
      $name = trim($this->update->message->text);
    } else {
      $name = $inlineName;
    }
    // Simple checks
    if (empty($name)) {
      return;
    }
    if (mb_strlen($name) < 3) {
      $this->replyWithMessage(
        'Запрос должен содержать минимум 3 символа.',
        Communicator::PARSE_MODE_PLAIN,
        new ReplyKeyboardRemove()
      );

      return;
    }

    // Cancel
    if (self::BTN_CANCEL == $name) {
      $this->replyWithMessage(
        'Команда отменена.',
        Communicator::PARSE_MODE_PLAIN,
        new ReplyKeyboardRemove()
      );

      return;
    }

    $users = $this->bus->getDoctrine()
      ->getRepository('KettariTelegramBundle:User')
      ->search($name);
    if (0 == count($users)) {
      $this->replyWithMessage(
        'Не нашёл ни одного пользователя, удовлетворяющего этим критериями.',
        Communicator::PARSE_MODE_PLAIN,
        new ReplyKeyboardRemove()
      );

      return;
    } elseif (count($users) > 1) {
      $this->replyWithMessage(
        sprintf(
          'Нашёл нескольких пользователей (%d, если быть точным), удовлетворяющих этим критериями. Пожалуйста, уточните запрос так, чтобы был найден только один.',
          count($users)
        ),
        Communicator::PARSE_MODE_PLAIN,
        new ReplyKeyboardRemove()
      );

      return;
    }

    /** @var \Kettari\TelegramBundle\Entity\User $foundUser */
    $foundUser = reset($users);
    $this->replyWithMessage(
      $this->getUserInformation($foundUser),
      Communicator::PARSE_MODE_HTML,
      $this->getReplyKeyboardMarkup_MainMenu()
    );

    $this->bus->createHook(
      $this->update,
      get_class($this),
      'handleUserMan_Menu',
      serialize($foundUser)
    );
  }

  /**
   * Returns string with information about user's roles, block status etc.
   *
   * @param User $user
   * @return string
   */
  private function getUserInformation(User $user)
  {
    $text = sprintf(
        '<b>%s</b>',
        trim($user->getFirstName().' '.$user->getLastName())
      ).PHP_EOL;
    $text .= 'В Талланто: '.trim(
        $user->getExternalFirstName().' '.$user->getExternalLastName()
      ).PHP_EOL;
    $text .= 'Телефон: '.$user->getPhone().PHP_EOL;
    $text .= 'Telegram ID: '.$user->getTelegramId().PHP_EOL;
    $text .= 'Заблокирован: '.($user->isBlocked() ? 'Да' : 'Нет').PHP_EOL;
    // Enumerate roles
    $text .= PHP_EOL.'<b>Роли:</b>'.PHP_EOL;
    /** @var \Kettari\TelegramBundle\Entity\Role $role */
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
    $replyMarkup = new ReplyKeyboardMarkup();
    $replyMarkup->one_time_keyboard = true;
    $replyMarkup->resize_keyboard = true;

    // Roles
    $rolesBtn = new KeyboardButton();
    $rolesBtn->text = self::BTN_ROLES_ADD;
    $replyMarkup->keyboard[][] = $rolesBtn;
    $rolesBtn = new KeyboardButton();
    $rolesBtn->text = self::BTN_ROLES_REMOVE;
    $replyMarkup->keyboard[][] = $rolesBtn;
    // Block
    $blockBtn = new KeyboardButton();
    $blockBtn->text = self::BTN_BLOCK;
    $replyMarkup->keyboard[][] = $blockBtn;
    // Cancel button
    $cancelBtn = new KeyboardButton();
    $cancelBtn->text = self::BTN_CANCEL;
    $replyMarkup->keyboard[][] = $cancelBtn;

    return $replyMarkup;
  }

  /**
   * Returns reply markup object.
   *
   * @return ReplyKeyboardMarkup
   */
  private function getReplyKeyboardMarkup_Cancel()
  {
    // Cancel button
    $cancelBtn = new KeyboardButton();
    $cancelBtn->text = self::BTN_CANCEL;

    // Keyboard
    $replyMarkup = new ReplyKeyboardMarkup();
    $replyMarkup->one_time_keyboard = true;
    $replyMarkup->resize_keyboard = true;
    $replyMarkup->keyboard[][] = $cancelBtn;

    return $replyMarkup;
  }

  /**
   * Handles /userman main menu.
   *
   * @param mixed $serializedFoundUser
   */
  public function handleUserMan_Menu($serializedFoundUser)
  {
    /** @var \Kettari\TelegramBundle\Entity\User $foundUser */
    $foundUser = unserialize($serializedFoundUser);
    // Selection
    $selection = trim($this->update->message->text);
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
        Communicator::PARSE_MODE_HTML,
        $this->getReplyKeyboardMarkup_Roles_Add($foundUser)
      );

      $this->bus->createHook(
        $this->update,
        get_class($this),
        'handleUserManRoleAdd',
        $serializedFoundUser
      );

      return;
    }

    // Remove roles option
    if (self::BTN_ROLES_REMOVE == $selection) {
      $this->replyWithMessage(
        'Какую роль убрать у пользователя?',
        Communicator::PARSE_MODE_HTML,
        $this->getReplyKeyboardMarkup_Roles_Remove($foundUser)
      );

      $this->bus->createHook(
        $this->update,
        get_class($this),
        'handleUserManRoleRemove',
        $serializedFoundUser
      );

      return;
    }

    // Change blocked status
    if (self::BTN_BLOCK == $selection) {
      $this->changeBlockState($foundUser);

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
   * @param User $foundUser
   * @return \unreal4u\TelegramAPI\Telegram\Types\ReplyKeyboardMarkup
   */
  private function getReplyKeyboardMarkup_Roles_Add($foundUser)
  {
    // Keyboard
    $replyMarkup = new ReplyKeyboardMarkup();
    $replyMarkup->one_time_keyboard = true;
    $replyMarkup->resize_keyboard = true;

    /** @var User $userToManipulate */
    if (is_null(
      $userToManipulate = $this->bus->getDoctrine()
        ->getRepository('KettariTelegramBundle:User')
        ->find($foundUser->getId())
    )) {
      $this->replyWithMessage(
        'Пользователь не найден :('
      );

      return null;
    }

    /** @var \Kettari\TelegramBundle\Entity\Role $role */
    foreach ($this->bus->getDoctrine()
               ->getRepository('KettariTelegramBundle:Role')
               ->findRegular() as $role) {
      if ($userToManipulate->getRoles()
        ->contains($role)) {
        continue;
      }
      $roleBtn = new KeyboardButton();
      $roleBtn->text = $role->getName();
      $replyMarkup->keyboard[][] = $roleBtn;
    }
    // Cancel button
    $cancelBtn = new KeyboardButton();
    $cancelBtn->text = self::BTN_CANCEL;
    $replyMarkup->keyboard[][] = $cancelBtn;

    return $replyMarkup;
  }

  /**
   * Returns reply markup object for Roles -> Remove
   *
   * @param User $foundUser
   * @return \unreal4u\TelegramAPI\Telegram\Types\ReplyKeyboardMarkup
   */
  private function getReplyKeyboardMarkup_Roles_Remove($foundUser)
  {
    // Keyboard
    $replyMarkup = new ReplyKeyboardMarkup();
    $replyMarkup->one_time_keyboard = true;
    $replyMarkup->resize_keyboard = true;

    /** @var \Kettari\TelegramBundle\Entity\Role $role */
    foreach ($foundUser->getRoles() as $role) {
      // Safe check
      if ($role->getAnonymous() || $role->getAdministrator()) {
        continue;
      }
      $roleBtn = new KeyboardButton();
      $roleBtn->text = $role->getName();
      $replyMarkup->keyboard[][] = $roleBtn;
    }
    // Cancel button
    $cancelBtn = new KeyboardButton();
    $cancelBtn->text = self::BTN_CANCEL;
    $replyMarkup->keyboard[][] = $cancelBtn;

    return $replyMarkup;
  }

  /**
   * Change state of the user.
   *
   * @param User $foundUser
   */
  public function changeBlockState(User $foundUser)
  {
    /** @var User $userToManipulate */
    if (is_null(
      $userToManipulate = $this->bus->getDoctrine()
        ->getRepository('KettariTelegramBundle:User')
        ->find($foundUser->getId())
    )) {
      $this->replyWithMessage(
        'Пользователь не найден :(',
        null,
        new ReplyKeyboardRemove()
      );

      return;
    }

    // Change state of blocking flag
    $userToManipulate->setBlocked(!$userToManipulate->isBlocked());
    $this->bus->getDoctrine()
      ->getManager()
      ->flush();

    $this->replyWithMessage('Статус блокровки изменён.');
    $this->replyWithMessage(
      $this->getUserInformation($userToManipulate),
      Communicator::PARSE_MODE_HTML,
      new ReplyKeyboardRemove()
    );
  }

  /**
   * Handles /userman add role to the user.
   *
   * @param mixed $serializedFoundUser
   */
  public function handleUserManRoleAdd($serializedFoundUser)
  {
    /** @var \Kettari\TelegramBundle\Entity\User $foundUser */
    $foundUser = unserialize($serializedFoundUser);
    // Selection
    $selection = trim($this->update->message->text);

    // Cancel
    if (self::BTN_CANCEL == $selection) {
      $this->replyWithMessage(
        'Команда отменена.',
        null,
        new ReplyKeyboardRemove()
      );

      return;
    }

    /** @var Role $roleToAdd */
    if (is_null(
      $roleToAdd = $this->bus->getDoctrine()
        ->getRepository('KettariTelegramBundle:Role')
        ->findOneByName($selection)
    )) {
      $this->replyWithMessage(
        'Роль не найдена в списке: '.$selection
      );

      return;
    }

    /** @var User $userToManipulate */
    if (is_null(
      $userToManipulate = $this->bus->getDoctrine()
        ->getRepository('KettariTelegramBundle:User')
        ->find($foundUser->getId())
    )) {
      $this->replyWithMessage(
        'Пользователь не найден :('
      );

      return;
    }

    // Add the role
    $userToManipulate->addRole($roleToAdd);
    $this->bus->getDoctrine()
      ->getManager()
      ->flush();

    $this->replyWithMessage('Роль добавлена.');
    $this->replyWithMessage(
      $this->getUserInformation($userToManipulate),
      Communicator::PARSE_MODE_HTML,
      new ReplyKeyboardRemove()
    );
  }

  /**
   * Handles /userman remove role from the user
   *
   * @param mixed $serializedFoundUser
   */
  public function handleUserManRoleRemove($serializedFoundUser)
  {
    /** @var \Kettari\TelegramBundle\Entity\User $foundUser */
    $foundUser = unserialize($serializedFoundUser);
    // Selection
    $selection = trim($this->update->message->text);

    // Cancel
    if (self::BTN_CANCEL == $selection) {
      $this->replyWithMessage(
        'Команда отменена.',
        null,
        new ReplyKeyboardRemove()
      );

      return;
    }

    /** @var Role $roleToRemove */
    if (is_null(
      $roleToRemove = $this->bus->getDoctrine()
        ->getRepository('KettariTelegramBundle:Role')
        ->findOneByName($selection)
    )) {
      $this->replyWithMessage(
        'Роль не найдена в списке: '.$selection
      );

      return;
    }

    /** @var User $userToManipulate */
    if (is_null(
      $userToManipulate = $this->bus->getDoctrine()
        ->getRepository('KettariTelegramBundle:User')
        ->find($foundUser->getId())
    )) {
      $this->replyWithMessage(
        'Пользователь не найден :('
      );

      return;
    }

    // Remove notifications that are bound to the role going to be removed
    /** @var \Kettari\TelegramBundle\Entity\Notification $notification */
    foreach ($userToManipulate->getNotifications() as $notification) {
      if ($roleToRemove->getPermissions()
        ->contains($notification->getPermission())) {
        $userToManipulate->removeNotification($notification);
      }
    }
    // Remove the role
    $userToManipulate->removeRole($roleToRemove);
    $this->bus->getDoctrine()
      ->getManager()
      ->flush();

    $this->replyWithMessage('Роль убрана.');
    $this->replyWithMessage(
      $this->getUserInformation($userToManipulate),
      Communicator::PARSE_MODE_HTML,
      new ReplyKeyboardRemove()
    );
  }

}