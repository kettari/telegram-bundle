<?php
/**
 * Created by PhpStorm.
 * User: ant
 * Date: 15.03.2017
 * Time: 18:14
 */

namespace Kaula\TelegramBundle\Command;


use Kaula\TelegramBundle\Entity\Notification;
use Kaula\TelegramBundle\Entity\Permission;
use Kaula\TelegramBundle\Entity\Role;
use Kaula\TelegramBundle\Exception\TelegramBundleException;
use Kaula\TelegramBundle\Telegram\Command\AbstractCommand as TelegramAbstractCommand;
use Kaula\TelegramBundle\Telegram\Command\HelpCommand;
use Kaula\TelegramBundle\Telegram\Command\ListRolesCommand;
use Kaula\TelegramBundle\Telegram\Command\PushCommand;
use Kaula\TelegramBundle\Telegram\Command\RegisterCommand;
use Kaula\TelegramBundle\Telegram\Command\SettingsCommand;
use Kaula\TelegramBundle\Telegram\Command\StartCommand;
use Kaula\TelegramBundle\Telegram\Command\UserManCommand;

class PopulateSchemaCommand extends AbstractCommand
{
  /**
   * @var Role
   */
  private $role_guest;

  /**
   * @var Role
   */
  private $role_registered;

  /**
   * @var Role
   */
  private $role_sysadmin;

  /**
   * Configures the current command.
   */
  protected function configure()
  {
    $this->setName('telegram:schema:populate')
      ->setDescription('Populates schema with initial info')
      ->setHelp(
        'Use this method to fill in roles, permissions and other essential tables with default information.'
      );
  }

  /**
   * Execute actual code of the command.
   */
  protected function executeCommand()
  {
    $this->io->title('Populate telegram-bundle tables');

    $this->io->write('Populating roles...');
    $this->populateRoles();
    $this->io->writeln('done.');

    $this->io->write('Populating permissions...');
    $this->populatePermissions();
    $this->io->writeln('done.');

    $this->io->write('Populating notifications...');
    $this->populateNotifications();
    $this->io->writeln('done.');

    $this->io->success('Done.');
  }

  /**
   * Populates roles.
   */
  private function populateRoles()
  {
    // Check there are no rows in the role table
    if (!$this->isRolesEmpty()) {
      throw new TelegramBundleException('Role table is not empty');
    }

    /** @var \Doctrine\Bundle\DoctrineBundle\Registry $d */
    $d = $this->getContainer()
      ->get('doctrine');
    $em = $d->getManager();

    // Guest
    $guest = new Role();
    $guest->setName('guest')
      ->setAnonymous(true);
    $em->persist($guest);
    $this->role_guest = $guest;

    // Registered
    $registered = new Role();
    $registered->setName('registered');
    $em->persist($registered);
    $this->role_registered = $registered;

    // Supervisor
    $supervisor = new Role();
    $supervisor->setName('supervisor');
    $em->persist($supervisor);

    // Sysadmin
    $sysadmin = new Role();
    $sysadmin->setName('sysadmin')
      ->setAdministrator(true);
    $em->persist($sysadmin);
    $this->role_sysadmin = $sysadmin;

    // Flush changes
    $em->flush();
  }

  /**
   * Returns true if roles are empty.
   *
   * @return bool
   */
  private function isRolesEmpty()
  {
    /** @var \Doctrine\Bundle\DoctrineBundle\Registry $d */
    $d = $this->getContainer()
      ->get('doctrine');

    return 0 == count(
        $d->getRepository('KaulaTelegramBundle:Role')
          ->findAll()
      );
  }

  /**
   * Populates permissions.
   */
  private function populatePermissions()
  {
    // Check there are no rows in the permission table
    if (!$this->isPermissionsEmpty()) {
      throw new TelegramBundleException('Permission table is not empty');
    }

    /** @var \Doctrine\Bundle\DoctrineBundle\Registry $d */
    $d = $this->getContainer()
      ->get('doctrine');
    $em = $d->getManager();

    // Permissions for the guest role
    $guest_permissions = [];
    // Permissions for the registered role
    $registered_permissions = [];
    // Permissions for the sysadmin role
    $sysadmin_permissions = [];

    // Guest commands
    $guest_permissions = array_merge(
      $guest_permissions,
      $this->populateCommandPermissionsRequired(StartCommand::class)
    );
    $guest_permissions = array_merge(
      $guest_permissions,
      $this->populateCommandPermissionsRequired(HelpCommand::class)
    );
    $guest_permissions = array_merge(
      $guest_permissions,
      $this->populateCommandPermissionsRequired(RegisterCommand::class)
    );

    // Registered commands
    $registered_permissions = array_merge(
      $registered_permissions,
      $this->populateCommandPermissionsRequired(SettingsCommand::class)
    );

    // Sysadmin
    $sysadmin_permissions = array_merge(
      $sysadmin_permissions,
      $this->populateCommandPermissionsRequired(ListRolesCommand::class)
    );
    $sysadmin_permissions = array_merge(
      $sysadmin_permissions,
      $this->populateCommandPermissionsRequired(PushCommand::class)
    );
    $sysadmin_permissions = array_merge(
      $sysadmin_permissions,
      $this->populateCommandPermissionsRequired(ListRolesCommand::class)
    );
    $sysadmin_permissions = array_merge(
      $sysadmin_permissions,
      $this->populateCommandPermissionsRequired(UserManCommand::class)
    );
    // 'new-register' notification → to sysadmin
    $sysadmin_permissions = array_merge(
      $sysadmin_permissions,
      $this->populateCommandNotificationPermissions(RegisterCommand::class)
    );

    // Assign permissions to the roles
    // Guest
    /** @var \Kaula\TelegramBundle\Entity\Permission $one_permission */
    foreach ($guest_permissions as $one_permission) {
      $this->role_guest->addPermission($one_permission);
    }

    // Registered
    /** @var \Kaula\TelegramBundle\Entity\Permission $one_permission */
    foreach ($registered_permissions as $one_permission) {
      $this->role_registered->addPermission($one_permission);
    }

    // Sysadmin
    /** @var \Kaula\TelegramBundle\Entity\Permission $one_permission */
    foreach ($sysadmin_permissions as $one_permission) {
      $this->role_sysadmin->addPermission($one_permission);
    }

    // Create permissions for well-known notifications
    $perm_self_update = new Permission();
    $perm_self_update->setName('receive notification self-update');
    $em->persist($perm_self_update);

    // Flush changes
    $em->flush();
  }

  /**
   * Returns true if permissions are empty.
   *
   * @return bool
   */
  private function isPermissionsEmpty()
  {
    /** @var \Doctrine\Bundle\DoctrineBundle\Registry $d */
    $d = $this->getContainer()
      ->get('doctrine');

    return 0 == count(
        $d->getRepository('KaulaTelegramBundle:Permission')
          ->findAll()
      );
  }

  /**
   * Populates permissions required for the command.
   *
   * @param $command
   * @return array
   */
  private function populateCommandPermissionsRequired($command)
  {
    /** @var \Doctrine\Bundle\DoctrineBundle\Registry $d */
    $d = $this->getContainer()
      ->get('doctrine');
    $em = $d->getManager();

    /** @var TelegramAbstractCommand $command */
    if (!is_subclass_of($command, TelegramAbstractCommand::class)) {
      throw new TelegramBundleException(
        'Invalid command class, not successor of the AbstractCommand: '.$command
      );
    }

    // Return this array with entities
    $permission_entities = [];

    // Get list of permission names required and create them
    $permission_names = $command::getRequiredPermissions();
    foreach ($permission_names as $perm_name) {
      $permission = new Permission();
      $permission->setName($perm_name);
      $em->persist($permission);

      // Return created object in the array
      $permission_entities[] = $permission;
    }

    // Flush changes
    $em->flush();

    return $permission_entities;
  }

  /**
   * Populates notification permissions for the command.
   *
   * @param $command
   * @return array
   */
  private function populateCommandNotificationPermissions($command)
  {
    /** @var \Doctrine\Bundle\DoctrineBundle\Registry $d */
    $d = $this->getContainer()
      ->get('doctrine');
    $em = $d->getManager();

    /** @var TelegramAbstractCommand $command */
    if (!is_subclass_of($command, TelegramAbstractCommand::class)) {
      throw new TelegramBundleException(
        'Invalid command class, not successor of the AbstractCommand: '.$command
      );
    }

    // Return this array with entities
    $permission_entities = [];

    // Get list of notification names required and create permissions for them
    $notification_names = $command::getDeclaredNotifications();
    foreach ($notification_names as $notif_name) {
      $permission = new Permission();
      $permission->setName(sprintf('receive notification %s', $notif_name));
      $em->persist($permission);

      // Return created object in the array
      $permission_entities[] = $permission;
    }

    // Flush changes
    $em->flush();

    return $permission_entities;
  }

  /**
   * Populates well-known notifications.
   */
  private function populateNotifications()
  {
    // Check there are no rows in the notification table
    if (!$this->isNotificationsEmpty()) {
      throw new TelegramBundleException('Notification table is not empty');
    }

    /** @var \Doctrine\Bundle\DoctrineBundle\Registry $d */
    $d = $this->getContainer()
      ->get('doctrine');
    $em = $d->getManager();

    /**
     * 'self-update'
     */
    if (is_null(
      $perm_self_update = $d->getRepository('KaulaTelegramBundle:Permission')
        ->findOneBy(['name' => 'receive notification self-update'])
    ))
    {
      throw new TelegramBundleException('Unable to get "receive notification self-update" permission');
    }

    // Create notification 'self-update'
    $notif_self_update = new Notification();
    $notif_self_update->setName('self-update')
      ->setSortOrder(99999)
      ->setTitle('Что нового в боте')
      ->setGuestDefault(true)
      ->setPermission($perm_self_update);
    $em->persist($notif_self_update);

    /**
     * 'new-register'
     */
    if (is_null(
      $perm_self_update = $d->getRepository('KaulaTelegramBundle:Permission')
        ->findOneBy(['name' => 'receive notification new-register'])
    ))
    {
      throw new TelegramBundleException('Unable to get "receive notification new-register" permission');
    }

    // Create notification 'self-update'
    $notif_new_register = new Notification();
    $notif_new_register->setName('new-register')
      ->setSortOrder(88888)
      ->setTitle('Новые регистрации')
      ->setPermission($perm_self_update);
    $em->persist($notif_new_register);

    // Flush changes
    $em->flush();
  }

  /**
   * Returns true if notifications are empty.
   *
   * @return bool
   */
  private function isNotificationsEmpty()
  {
    /** @var \Doctrine\Bundle\DoctrineBundle\Registry $d */
    $d = $this->getContainer()
      ->get('doctrine');

    return 0 == count(
        $d->getRepository('KaulaTelegramBundle:Notification')
          ->findAll()
      );
  }

}