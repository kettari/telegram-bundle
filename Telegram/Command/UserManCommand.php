<?php
/**
 * Created by PhpStorm.
 * User: ant
 * Date: 16.03.2017
 * Time: 18:18
 */

namespace Kaula\TelegramBundle\Telegram\Command;


use Kaula\TelegramBundle\Repository\UserRepository;

class UserManCommand extends AbstractCommand
{

  static public $name = 'userman';
  static public $description = 'Управление ролями пользователей';
  static public $required_permissions = ['execute command userman'];

  /**
   * Executes command.
   */
  public function execute()
  {
    $this->replyWithMessage('Укажите часть ФИО, никнейма либо номер телефона:');

    // Create the hook to handle user's reply
    $this->getBus()
      ->getHooker()
      ->createHook(
        $this->getUpdate(),
        get_class($this),
        'handleUserMan'
      );
  }

  /**
   * Handles /userman user credentials request.
   */
  public function handleUserMan()
  {
    $name = $this->getUpdate()->message->text;
    if (empty($name)) {
      return;
    }
    //return;
    $d = $this->getBus()
      ->getBot()
      ->getContainer()
      ->get('doctrine');
    $em = $d->getManager();
    /** @var UserRepository $r */
    $r = $d->getRepository('KaulaTelegramBundle:User');
    $users = $r->findAnyone($name);
    if (0 == count($users)) {
      $this->replyWithMessage(
        'Не нашёл ни одного пользователя, удовлетворяющего этим критериями.'
      );

      return;
    } elseif (count($users) > 1) {
      $this->replyWithMessage(
        'Нашёл нескольких пользователей, удовлетворяющих этим критериями. Пожалуйста, уточните их так, чтобы был найден только один.'
      );

      return;
    }

    /** @var \Kaula\TelegramBundle\Entity\User $found_user */
    $found_user = reset($users);
    $this->replyWithMessage(
      'Нашёл: '.$found_user->getExternalLastName().' '.
      $found_user->getExternalFirstName()
    );
  }


}