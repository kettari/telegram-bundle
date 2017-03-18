<?php
/**
 * Created by PhpStorm.
 * User: ant
 * Date: 18.03.2017
 * Time: 18:24
 */

namespace Kaula\TelegramBundle\Telegram\Command;


use Kaula\TelegramBundle\Entity\Role;
use Kaula\TelegramBundle\Entity\User;

abstract class AbstractUserAwareCommand extends AbstractCommand {

  /**
   * Initialize command.
   *
   * @return AbstractUserAwareCommand
   */
  public function initialize() {
    $this->updateUserInformation();

    return $this;
  }

  /**
   * Updates user information in the database.
   *
   * @param \Kaula\TelegramBundle\Entity\User $user
   */
  protected function updateUserInformation(User $user = NULL) {
    $tu = $this->getUpdate()->message->from;
    $d = $this->getBus()->getBot()
      ->getContainer()
      ->get('doctrine');
    $em = $d->getManager();

    // Find user object. If not found, create new
    if (is_null($user)) {
      $user = $d->getRepository('KaulaTelegramBundle:User')
        ->find($tu->id);
      if (!$user) {
        $user = new User();
      }
    }
    // Detect changes
    if (($user->getId() != $tu->id) ||
      ($user->getFirstName() != $tu->first_name) ||
      ($user->getLastName() != $tu->last_name) ||
      ($user->getUsername() != $tu->username)) {
      // Update information
      $user->setId($tu->id)
        ->setFirstName($tu->first_name)
        ->setLastName($tu->last_name)
        ->setUsername($tu->username);
      $em->persist($user);
    }

    // Add user to the "guest" role
    // Find role object
    $roles = $d->getRepository('KaulaTelegramBundle:Role')
      ->findBy(['anonymous' => TRUE]);
    if (0 == count($roles)) {
      throw new \LogicException('Roles for guests not found');
    }

    // DEBUG
/*    $l = $this->getBus()->getBot()->getContainer()->get('logger');
    $cloner = new VarCloner();
    $dumper = new CliDumper();
    $dump = $dumper->dump($cloner->cloneVar($user->getRoles()), TRUE);
    $l->debug('User roles', ['roles' => $dump]);*/

    /** @var Role $single_role */
    foreach ($roles as $single_role) {
      if (!$user->getRoles()->contains($single_role)) {
        $user->addRole($single_role);
        $em->persist($user);
      }
    }

    // Commit changes
    $em->flush();
  }

}