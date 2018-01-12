<?php
/**
 * Created by PhpStorm.
 * User: ant
 * Date: 15.03.2017
 * Time: 18:14
 */

namespace Kaula\TelegramBundle\Command;

use GuzzleHttp\Client;
use Tallanto\ClientApiBundle\Api\Method\TallantoGetUsersMethod;
use Tallanto\ClientApiBundle\Api\TallantoApiClient;
use Tallanto\ClientApiBundle\Api\TallantoPump;

class UserInvalidateCommand extends AbstractCommand
{

  /**
   * Configures the current command.
   */
  protected function configure()
  {
    $this->setName('telegram:users:invalidate')
      ->setDescription('Invalidates employees')
      ->setHelp(
        'Blocks fired employees, checks inactive user accounts in the Tallanto.'
      );
    $this->setBlocking(true);
  }

  /**
   * Execute actual code of the command.
   *
   * @throws \Exception
   */
  protected function executeCommand()
  {
    $this->io->writeln('Invalidating users');

    $c = $this->getContainer();
    $url = $c->getParameter('tallanto.url');
    $login = $c->getParameter('tallanto.login');
    $password = $c->getParameter('tallanto.token');
    $users = $this->downloadUsers($url, $login, $password);

    $this->io->writeln(sprintf('Loaded %d users', count($users)));
    $this->blockInactiveUsers($users);
    $this->io->success('Users invalidated.');
  }

  /**
   * Downloads users from Tallanto matching $phone.
   *
   * @param string $url
   * @param string $login
   * @param string $password
   * @return array Array of contact objects
   * @throws \Exception
   */
  private function downloadUsers($url, $login, $password)
  {
    $this->logger->debug(
      'About to download users from the Tallanto',
      [
        'url'   => $url,
        'login' => $login,
      ]
    );

    // Create Guzzle client
    $client = new Client(['base_uri' => $url]);
    // Create method
    $method = new TallantoGetUsersMethod();
    $method->setLogin($login)
      ->setPassword($password);
    // Create Tallanto API client
    $api = new TallantoApiClient($client, $this->logger);
    // Download all users matching phone
    $pump = new TallantoPump($api, $method);
    $items = $pump->suck();

    // Make array of objects
    $existingUsers = $method->getUsers($items);

    $this->logger->debug(
      'Downloaded {users_count} items from the Tallanto',
      [
        'users_count'    => count($existingUsers),
        'existing_users' => substr(
          print_r($existingUsers, true),
          0,
          1024
        ),
      ]
    );

    // Convert array of array to array of users objects
    return $existingUsers;
  }

  /**
   * Blocks telegram accounts for inactive users.
   *
   * @param array $users
   */
  private function blockInactiveUsers($users)
  {
    $d = $this->getContainer()
      ->get('doctrine');
    $em = $d->getManager();

    /** @var \Tallanto\Api\Entity\User $userItem */
    foreach ($users as $userItem) {
      /** @var \Kaula\TelegramBundle\Entity\User $dbUser */
      if (is_null(
        $dbUser = $d->getRepository('KaulaTelegramBundle:User')
          ->findOneBy(
            [
              'tallanto_user_id' => $userItem->getId(),
              'blocked'          => false,
            ]
          )
      )) {
        // Tallanto user not found in the list of telegram users or is blocked already. It's OK
        continue;
      }

      if (('Active' != $userItem->getUserStatus()) ||
        ('Active' != $userItem->getEmployeeStatus())) {
        $dbUser->setBlocked(true);
        $this->io->writeln(
          sprintf(
            'User "%s" blocked',
            trim($dbUser->getLastName().' '.$dbUser->getFirstName())
          )
        );
        $this->logger->info(
          sprintf(
            'User "%s" blocked',
            trim($dbUser->getLastName().' '.$dbUser->getFirstName())
          )
        );
      }
    }
    $em->flush();
  }

}