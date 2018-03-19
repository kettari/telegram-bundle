<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram\Command;

use Kettari\TelegramBundle\Telegram\CommandBusInterface;
use Kettari\TelegramBundle\Telegram\Communicator;
use Kettari\TelegramBundle\Telegram\CommunicatorInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Translation\TranslatorInterface;
use unreal4u\TelegramAPI\Telegram\Types\Update;

class ListRolesCommand extends AbstractCommand
{

  static public $name = 'listroles';
  static public $description = 'command.listroles.description';
  static public $requiredPermissions = ['execute command listroles'];

  /**
   * @var \Symfony\Bridge\Doctrine\RegistryInterface
   */
  private $doctrine;

  /**
   * ListRolesCommand constructor.
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

    // Collect all roles and permissions from database
    $rolesAndPermissions = $this->getRolesAndPermissions();
    $text = $this->trans->trans('command.listroles.list_of_roles').PHP_EOL.
      PHP_EOL;
    if (count($rolesAndPermissions)) {
      foreach ($rolesAndPermissions as $roleName => $permissions) {
        $text .= '<b>'.$roleName.'</b>'.PHP_EOL;
        foreach ($permissions as $permissionItem) {
          $text .= $permissionItem.PHP_EOL;
        }
        $text .= PHP_EOL;
      }
    } else {
      $text .= $this->trans->trans('command.listroles.roles_undefined');
    }

    $this->replyWithMessage($update, $text, Communicator::PARSE_MODE_HTML);
  }


  /**
   * Returns array with roles and permissions defined in the database.
   *
   * @return array
   */
  private function getRolesAndPermissions()
  {
    $result = [];
    $roles = $this->doctrine->getRepository('KettariTelegramBundle:Role')
      ->findAll();
    /** @var \Kettari\TelegramBundle\Entity\Role $roleItem */
    foreach ($roles as $roleItem) {
      $permissions = $roleItem->getPermissions();
      $perms = [];
      /** @var \Kettari\TelegramBundle\Entity\Permission $permissionItem */
      foreach ($permissions as $permissionItem) {
        $perms[] = $permissionItem->getName();
      }
      asort($perms);
      $result[$roleItem->getName()] = $perms;
    }
    ksort($result);

    return $result;
  }

}