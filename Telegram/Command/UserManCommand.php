<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram\Command;


use Kettari\TelegramBundle\Entity\Role;
use Kettari\TelegramBundle\Entity\User;
use Kettari\TelegramBundle\Telegram\CommandBusInterface;
use Kettari\TelegramBundle\Telegram\Communicator;
use Kettari\TelegramBundle\Telegram\CommunicatorInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Translation\TranslatorInterface;
use unreal4u\TelegramAPI\Telegram\Types\KeyboardButton;
use unreal4u\TelegramAPI\Telegram\Types\ReplyKeyboardMarkup;
use unreal4u\TelegramAPI\Telegram\Types\ReplyKeyboardRemove;
use unreal4u\TelegramAPI\Telegram\Types\Update;

class UserManCommand extends AbstractCommand
{

  const LIST_MARK = "\xF0\x9F\x94\xB9";
  const BTN_ROLES_ADD = 'command.userman.add_roles';
  const BTN_ROLES_REMOVE = 'command.userman.remove_roles';
  const BTN_BLOCK = 'command.userman.flip_blocking';
  const BTN_CANCEL = 'command.button.cancel';
  static public $name = 'userman';
  static public $description = 'command.userman.description';
  static public $requiredPermissions = ['execute command userman'];

  /**
   * @var \Symfony\Bridge\Doctrine\RegistryInterface
   */
  private $doctrine;

  /**
   * UserManCommand constructor.
   *
   * @param \Psr\Log\LoggerInterface $logger
   * @param \Kettari\TelegramBundle\Telegram\CommandBusInterface $bus
   * @param \Symfony\Component\Translation\TranslatorInterface $translator
   * @param \Symfony\Bridge\Doctrine\RegistryInterface $doctrine
   * @param \Kettari\TelegramBundle\Telegram\CommunicatorInterface $communicator
   */
  public function __construct(
    LoggerInterface $logger,
    CommandBusInterface $bus,
    TranslatorInterface $translator,
    RegistryInterface $doctrine,
    CommunicatorInterface $communicator
  ) {
    parent::__construct($logger, $bus, $translator, $communicator);
    $this->doctrine = $doctrine;
  }

  /**
   * @inheritdoc
   */
  public function execute(Update $update, string $parameter = '')
  {
    // This command is available only in private chat
    if ('private' != $update->message->chat->type) {
      $this->replyWithMessage(
        $update,
        $this->trans->trans('command.private_only')
      );

      return;
    }

    if (!empty($parameter)) {
      $this->showUserManMenu($update, $parameter);

      return;
    }

    $this->replyWithMessage(
      $update,
      $this->trans->trans('command.userman.specify_search_string'),
      Communicator::PARSE_MODE_PLAIN,
      $this->getReplyKeyboardMarkup_Cancel()
    );

    // Create the hook to handle user's reply
    $this->bus->createHook(
      $update,
      'kettari_telegram.command.userman',
      'showUserManMenu'
    );
  }

  /**
   * Handles /userman user credentials request.
   *
   * @param \unreal4u\TelegramAPI\Telegram\Types\Update $update
   * @param string $inlineName
   * @throws \Exception
   */
  public function showUserManMenu(Update $update, $inlineName = '')
  {
    if (empty($inlineName)) {
      $name = trim($update->message->text);
    } else {
      $name = $inlineName;
    }
    // Simple checks
    if (empty($name)) {
      return;
    }
    if (mb_strlen($name) < 3) {
      $this->replyWithMessage(
        $update,
        $this->trans->trans('command.userman.request_min_count_length'),
        Communicator::PARSE_MODE_PLAIN,
        new ReplyKeyboardRemove()
      );

      return;
    }

    // Cancel
    if (self::BTN_CANCEL == $name) {
      $this->replyWithMessage(
        $update,
        $this->trans->trans('command.cancelled'),
        Communicator::PARSE_MODE_PLAIN,
        new ReplyKeyboardRemove()
      );

      return;
    }

    $users = $this->doctrine->getRepository('KettariTelegramBundle:User')
      ->search($name);
    if (0 == count($users)) {
      $this->replyWithMessage(
        $update,
        $this->trans->trans('command.userman.no_users_found'),
        Communicator::PARSE_MODE_PLAIN,
        new ReplyKeyboardRemove()
      );

      return;
    } elseif (count($users) > 1) {
      $this->replyWithMessage(
        $update,
        $this->trans->trans(
          'command.userman.multiple_users_found',
          ['command.userman.multiple_users_found' => count($users)]
        ),
        Communicator::PARSE_MODE_PLAIN,
        new ReplyKeyboardRemove()
      );

      return;
    }

    /** @var \Kettari\TelegramBundle\Entity\User $foundUser */
    $foundUser = reset($users);
    $this->replyWithMessage(
      $update,
      $this->getUserInformation($foundUser),
      Communicator::PARSE_MODE_HTML,
      $this->getReplyKeyboardMarkup_MainMenu()
    );

    $this->bus->createHook(
      $update,
      'kettari_telegram.command.userman',
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
    $text = $this->trans->trans(
      'command.userman.user_info',
      [
        '%full_name%' => trim(
          $user->getFirstName().' '.$user->getLastName()
        ),
        '%full_external_name%' => trim(
          $user->getExternalFirstName().' '.$user->getExternalLastName()
        ),
        '%username%' => $user->getUsername(),
        '%phone_number%' => $user->getPhone(),
        '%telegram_id%' => $user->getTelegramId(),
        '%blocking_status%' => $user->isBlocked() ? $this->trans->trans(
          'command.option.yes'
        ) : $this->trans->trans('command.option.no'),
      ]
    );
    $text .= PHP_EOL;
    // Enumerate roles
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
    $rolesBtn->text = $this->trans->trans(self::BTN_ROLES_ADD);
    $replyMarkup->keyboard[][] = $rolesBtn;
    $rolesBtn = new KeyboardButton();
    $rolesBtn->text = $this->trans->trans(self::BTN_ROLES_REMOVE);
    $replyMarkup->keyboard[][] = $rolesBtn;
    // Block
    $blockBtn = new KeyboardButton();
    $blockBtn->text = $this->trans->trans(self::BTN_BLOCK);
    $replyMarkup->keyboard[][] = $blockBtn;
    // Cancel button
    $cancelBtn = new KeyboardButton();
    $cancelBtn->text = $this->trans->trans(self::BTN_CANCEL);
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
    $cancelBtn->text = $this->trans->trans(self::BTN_CANCEL);

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
   * @param \unreal4u\TelegramAPI\Telegram\Types\Update $update
   * @param mixed $serializedFoundUser
   */
  public function handleUserMan_Menu(Update $update, $serializedFoundUser)
  {
    /** @var \Kettari\TelegramBundle\Entity\User $foundUser */
    $foundUser = unserialize($serializedFoundUser);
    // Selection
    $selection = trim($update->message->text);
    // Simple checks
    if (empty($selection)) {
      return;
    }

    // Cancel
    if ($this->trans->trans(self::BTN_CANCEL) == $selection) {
      $this->replyWithMessage(
        $update,
        $this->trans->trans('command.cancelled'),
        Communicator::PARSE_MODE_PLAIN,
        new ReplyKeyboardRemove()
      );

      return;
    }

    // Add roles option
    if ($this->trans->trans(self::BTN_ROLES_ADD) == $selection) {
      $this->replyWithMessage(
        $update,
        $this->trans->trans('command.userman.select_role_to_add'),
        Communicator::PARSE_MODE_HTML,
        $this->getReplyKeyboardMarkup_Roles_Add($update, $foundUser)
      );

      $this->bus->createHook(
        $update,
        'kettari_telegram.command.userman',
        'handleUserManRoleAdd',
        $serializedFoundUser
      );

      return;
    }

    // Remove roles option
    if ($this->trans->trans(self::BTN_ROLES_REMOVE) == $selection) {
      $this->replyWithMessage(
        $update,
        $this->trans->trans('command.userman.select_role_to_remove'),
        Communicator::PARSE_MODE_HTML,
        $this->getReplyKeyboardMarkup_Roles_Remove($foundUser)
      );

      $this->bus->createHook(
        $update,
        'kettari_telegram.command.userman',
        'handleUserManRoleRemove',
        $serializedFoundUser
      );

      return;
    }

    // Change blocked status
    if ($this->trans->trans(self::BTN_BLOCK) == $selection) {
      $this->changeBlockState($update, $foundUser);

      return;
    }

    $this->replyWithMessage(
      $update,
      $this->trans->trans('command.userman.invalid_option'),
      Communicator::PARSE_MODE_PLAIN,
      new ReplyKeyboardRemove()
    );
  }

  /**
   * Returns reply markup object for Roles -> Add
   *
   * @param \unreal4u\TelegramAPI\Telegram\Types\Update $update
   * @param User $foundUser
   * @return \unreal4u\TelegramAPI\Telegram\Types\ReplyKeyboardMarkup
   */
  private function getReplyKeyboardMarkup_Roles_Add(Update $update, $foundUser)
  {
    // Keyboard
    $replyMarkup = new ReplyKeyboardMarkup();
    $replyMarkup->one_time_keyboard = true;
    $replyMarkup->resize_keyboard = true;

    /** @var User $userToManipulate */
    if (is_null(
      $userToManipulate = $this->doctrine->getRepository(
        'KettariTelegramBundle:User'
      )
        ->find($foundUser->getId())
    )) {
      $this->replyWithMessage(
        $update,
        $this->trans->trans('command.userman.user_not_found')
      );

      return null;
    }

    /** @var \Kettari\TelegramBundle\Entity\Role $role */
    foreach ($this->doctrine->getRepository('KettariTelegramBundle:Role')
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
    $cancelBtn->text = $this->trans->trans(self::BTN_CANCEL);
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
    $cancelBtn->text = $this->trans->trans(self::BTN_CANCEL);
    $replyMarkup->keyboard[][] = $cancelBtn;

    return $replyMarkup;
  }

  /**
   * Change state of the user.
   *
   * @param \unreal4u\TelegramAPI\Telegram\Types\Update $update
   * @param User $foundUser
   */
  public function changeBlockState(Update $update, User $foundUser)
  {
    /** @var User $userToManipulate */
    if (is_null(
      $userToManipulate = $this->doctrine->getRepository(
        'KettariTelegramBundle:User'
      )
        ->find($foundUser->getId())
    )) {
      $this->replyWithMessage(
        $update,
        $this->trans->trans('command.userman.user_not_found'),
        Communicator::PARSE_MODE_PLAIN,
        new ReplyKeyboardRemove()
      );

      return;
    }

    // Change state of blocking flag
    $userToManipulate->setBlocked(!$userToManipulate->isBlocked());
    $this->doctrine->getManager()
      ->flush();

    $this->replyWithMessage(
      $update,
      $this->trans->trans('command.userman.blocking_status_changed')
    );
    $this->replyWithMessage(
      $update,
      $this->getUserInformation($userToManipulate),
      Communicator::PARSE_MODE_HTML,
      new ReplyKeyboardRemove()
    );
  }

  /**
   * Handles /userman add role to the user.
   *
   * @param \unreal4u\TelegramAPI\Telegram\Types\Update $update
   * @param mixed $serializedFoundUser
   */
  public function handleUserManRoleAdd(Update $update, $serializedFoundUser)
  {
    /** @var \Kettari\TelegramBundle\Entity\User $foundUser */
    $foundUser = unserialize($serializedFoundUser);
    // Selection
    $selection = trim($update->message->text);

    // Cancel
    if ($this->trans->trans(self::BTN_CANCEL) == $selection) {
      $this->replyWithMessage(
        $update,
        $this->trans->trans('command.cancelled'),
        Communicator::PARSE_MODE_PLAIN,
        new ReplyKeyboardRemove()
      );

      return;
    }

    /** @var Role $roleToAdd */
    if (is_null(
      $roleToAdd = $this->doctrine->getRepository('KettariTelegramBundle:Role')
        ->findOneByName($selection)
    )) {
      $this->replyWithMessage(
        $update,
        $this->trans->trans(
          'command.userman.role_not_found',
          ['%role_name%' => $selection]
        )
      );

      return;
    }

    /** @var User $userToManipulate */
    if (is_null(
      $userToManipulate = $this->doctrine->getRepository(
        'KettariTelegramBundle:User'
      )
        ->find($foundUser->getId())
    )) {
      $this->replyWithMessage(
        $update,
        $this->trans->trans('command.userman.user_not_found')
      );

      return;
    }

    // Add the role
    $userToManipulate->addRole($roleToAdd);
    $this->doctrine->getManager()
      ->flush();

    $this->replyWithMessage(
      $update,
      $this->trans->trans('command.userman.role_added')
    );
    $this->replyWithMessage(
      $update,
      $this->getUserInformation($userToManipulate),
      Communicator::PARSE_MODE_HTML,
      new ReplyKeyboardRemove()
    );
  }

  /**
   * Handles /userman remove role from the user
   *
   * @param \unreal4u\TelegramAPI\Telegram\Types\Update $update
   * @param mixed $serializedFoundUser
   */
  public function handleUserManRoleRemove(Update $update, $serializedFoundUser)
  {
    /** @var \Kettari\TelegramBundle\Entity\User $foundUser */
    $foundUser = unserialize($serializedFoundUser);
    // Selection
    $selection = trim($update->message->text);

    // Cancel
    if ($this->trans->trans(self::BTN_CANCEL) == $selection) {
      $this->replyWithMessage(
        $update,
        $this->trans->trans('command.cancelled'),
        Communicator::PARSE_MODE_PLAIN,
        new ReplyKeyboardRemove()
      );

      return;
    }

    /** @var Role $roleToRemove */
    if (is_null(
      $roleToRemove = $this->doctrine->getRepository(
        'KettariTelegramBundle:Role'
      )
        ->findOneByName($selection)
    )) {
      $this->replyWithMessage(
        $update,
        $this->trans->trans(
          'command.userman.role_not_found',
          ['%role_name%' => $selection]
        )
      );

      return;
    }

    /** @var User $userToManipulate */
    if (is_null(
      $userToManipulate = $this->doctrine->getRepository(
        'KettariTelegramBundle:User'
      )
        ->find($foundUser->getId())
    )) {
      $this->replyWithMessage(
        $update,
        $this->trans->trans('command.userman.user_not_found')
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
    $this->doctrine->getManager()
      ->flush();

    $this->replyWithMessage(
      $update,
      $this->trans->trans('command.userman.role_removed')
    );
    $this->replyWithMessage(
      $update,
      $this->getUserInformation($userToManipulate),
      Communicator::PARSE_MODE_HTML,
      new ReplyKeyboardRemove()
    );
  }

}