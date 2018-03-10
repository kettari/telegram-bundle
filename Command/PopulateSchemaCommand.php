<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Command;


use Kettari\TelegramBundle\Entity\Notification;
use Kettari\TelegramBundle\Entity\Permission;
use Kettari\TelegramBundle\Entity\Role;
use Kettari\TelegramBundle\Exception\TelegramBundleException;
use Kettari\TelegramBundle\Telegram\Command\AbstractCommand as TelegramAbstractCommand;
use Kettari\TelegramBundle\Telegram\Command\HelpCommand;
use Kettari\TelegramBundle\Telegram\Command\ListRolesCommand;
use Kettari\TelegramBundle\Telegram\Command\PushCommand;
use Kettari\TelegramBundle\Telegram\Command\RegisterCommand;
use Kettari\TelegramBundle\Telegram\Command\SettingsCommand;
use Kettari\TelegramBundle\Telegram\Command\StartCommand;
use Kettari\TelegramBundle\Telegram\Command\UserManCommand;

class PopulateSchemaCommand extends AbstractCommand
{
  /**
   * @var Role
   */
  private $roleGuest;

  /**
   * @var Role
   */
  private $roleRegistered;

  /**
   * @var Role
   */
  private $roleSysadmin;

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
  protected function populateRoles()
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
    $this->roleGuest = $guest;

    // Registered
    $registered = new Role();
    $registered->setName('registered');
    $em->persist($registered);
    $this->roleRegistered = $registered;

    // Supervisor
    $supervisor = new Role();
    $supervisor->setName('supervisor');
    $em->persist($supervisor);

    // Sysadmin
    $sysadmin = new Role();
    $sysadmin->setName('sysadmin')
      ->setAdministrator(true);
    $em->persist($sysadmin);
    $this->roleSysadmin = $sysadmin;

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
        $d->getRepository('KettariTelegramBundle:Role')
          ->findAll()
      );
  }

  /**
   * Populates permissions.
   */
  protected function populatePermissions()
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
    $guestPermissions = [];
    // Permissions for the registered role
    $registeredPermissions = [];
    // Permissions for the sysadmin role
    $sysadminPermissions = [];

    // Guest commands
    $guestPermissions = array_merge(
      $guestPermissions,
      $this->populateCommandPermissionsRequired(StartCommand::class)
    );
    $guestPermissions = array_merge(
      $guestPermissions,
      $this->populateCommandPermissionsRequired(HelpCommand::class)
    );
    $guestPermissions = array_merge(
      $guestPermissions,
      $this->populateCommandPermissionsRequired(RegisterCommand::class)
    );

    // Registered commands
    $registeredPermissions = array_merge(
      $registeredPermissions,
      $this->populateCommandPermissionsRequired(SettingsCommand::class)
    );

    // Sysadmin
    $sysadminPermissions = array_merge(
      $sysadminPermissions,
      $this->populateCommandPermissionsRequired(ListRolesCommand::class)
    );
    $sysadminPermissions = array_merge(
      $sysadminPermissions,
      $this->populateCommandPermissionsRequired(PushCommand::class)
    );
    $sysadminPermissions = array_merge(
      $sysadminPermissions,
      $this->populateCommandPermissionsRequired(UserManCommand::class)
    );
    // 'new-register' notification → to sysadmin
    $sysadminPermissions = array_merge(
      $sysadminPermissions,
      $this->populateCommandNotificationPermissions(RegisterCommand::class)
    );

    // Assign permissions to the roles
    // Guest
    /** @var \Kettari\TelegramBundle\Entity\Permission $one_permission */
    foreach ($guestPermissions as $one_permission) {
      $this->roleGuest->addPermission($one_permission);
    }

    // Registered
    /** @var \Kettari\TelegramBundle\Entity\Permission $one_permission */
    foreach ($registeredPermissions as $one_permission) {
      $this->roleRegistered->addPermission($one_permission);
    }

    // Sysadmin
    /** @var \Kettari\TelegramBundle\Entity\Permission $one_permission */
    foreach ($sysadminPermissions as $one_permission) {
      $this->roleSysadmin->addPermission($one_permission);
    }

    // Create permissions for well-known notifications
    $perm_self_update = new Permission();
    $perm_self_update->setName('receive notification self-update');
    $em->persist($perm_self_update);
    // Assign 'self-update' permission to guest
    $this->roleGuest->addPermission($perm_self_update);

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
        $d->getRepository('KettariTelegramBundle:Permission')
          ->findAll()
      );
  }

  /**
   * Populates permissions required for the command.
   *
   * @param $command
   * @return array
   */
  protected function populateCommandPermissionsRequired($command)
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
    $permissionEntities = [];

    // Get list of permission names required and create them
    $permissionNames = $command::getRequiredPermissions();
    foreach ($permissionNames as $permName) {
      $permission = new Permission();
      $permission->setName($permName);
      $em->persist($permission);

      // Return created object in the array
      $permissionEntities[] = $permission;
    }

    // Flush changes
    $em->flush();

    return $permissionEntities;
  }

  /**
   * Populates notification permissions for the command.
   *
   * @param $command
   * @return array
   */
  protected function populateCommandNotificationPermissions($command)
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
    $permissionEntities = [];

    // Get list of notification names required and create permissions for them
    $notificationNames = $command::getDeclaredNotifications();
    foreach ($notificationNames as $notifName) {
      $permission = new Permission();
      $permission->setName(sprintf('receive notification %s', $notifName));
      $em->persist($permission);

      // Return created object in the array
      $permissionEntities[] = $permission;
    }

    // Flush changes
    $em->flush();

    return $permissionEntities;
  }

  /**
   * Populates well-known notifications.
   */
  protected function populateNotifications()
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
      $permSelfUpdate = $d->getRepository('KettariTelegramBundle:Permission')
        ->findOneBy(['name' => 'receive notification self-update'])
    ))
    {
      throw new TelegramBundleException('Unable to get "receive notification self-update" permission');
    }

    // Create notification 'self-update'
    $notifSelfUpdate = new Notification();
    $notifSelfUpdate->setName('self-update')
      ->setSortOrder(99999)
      ->setTitle('Что нового в боте')
      ->setUserDefault(true)
      ->setPermission($permSelfUpdate);
    $em->persist($notifSelfUpdate);

    /**
     * 'new-register'
     */
    if (is_null(
      $permNewRegister = $d->getRepository('KettariTelegramBundle:Permission')
        ->findOneBy(['name' => 'receive notification new-register'])
    ))
    {
      throw new TelegramBundleException('Unable to get "receive notification new-register" permission');
    }

    // Create notification 'new-register'
    $notifNewRegister = new Notification();
    $notifNewRegister->setName('new-register')
      ->setSortOrder(88888)
      ->setTitle('Новые регистрации')
      ->setPermission($permNewRegister);
    $em->persist($notifNewRegister);

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
        $d->getRepository('KettariTelegramBundle:Notification')
          ->findAll()
      );
  }

}