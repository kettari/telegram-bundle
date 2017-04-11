<?php
/**
 * Created by PhpStorm.
 * User: ant
 * Date: 15.03.2017
 * Time: 18:14
 */

namespace Kaula\TelegramBundle\Command;

use Tallanto\Api\Aggregator\UserAggregator;
use Tallanto\Api\Provider\Http\Request;
use Tallanto\Api\Provider\Http\ServiceProvider;

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
  }

  /**
   * Execute actual code of the command.
   */
  protected function executeCommand()
  {
    $this->io->writeln('Invalidating users');
    $user_aggregator = $this->loadTallantoUsers();
    $this->io->writeln(sprintf('Loaded %d users', $user_aggregator->count()));
    $this->blockInactiveUsers($user_aggregator);;
    $this->io->success('Users invalidated.');
  }

  /**
   * Loads users from the Tallanto.
   *
   * @return \Tallanto\Api\Aggregator\UserAggregator
   */
  private function loadTallantoUsers()
  {
    $c = $this->getContainer();

    // Create HTTP Request object for ServiceProvider
    $request = new Request();
    $request->setLogger($c->get('logger'))
      ->setUrl($c->getParameter('tallanto.url'))
      ->setMethod('/api/v1/users')
      ->setLogin($c->getParameter('tallanto.login'))
      ->setApiHash($c->getParameter('tallanto.token'));

    // Create ServiceProvider object
    $provider = new ServiceProvider($request);
    $provider->setLogger($c->get('logger'))
      ->setPageSize(1000);

    // Create contacts aggregator
    $user_aggregator = new UserAggregator($provider);
    $user_aggregator->searchEx(null);

    return $user_aggregator;
  }

  /**
   * Blocks telegram accounts for inactive users.
   *
   * @param \Tallanto\Api\Aggregator\UserAggregator $user_aggregator
   */
  private function blockInactiveUsers($user_aggregator)
  {
    $d = $this->getContainer()
      ->get('doctrine');
    $em = $d->getManager();

    for ($iterator = $user_aggregator->getIterator(); $iterator->valid(
    ); $iterator->next()) {
      /** @var \Tallanto\Api\Entity\User $tallanto_user */
      $tallanto_user = $iterator->current();

      /** @var \Kaula\TelegramBundle\Entity\User $db_user */
      if (is_null(
        $db_user = $d->getRepository('KaulaTelegramBundle:User')
          ->findOneBy(
            [
              'tallanto_user_id' => $tallanto_user->getId(),
              'blocked'          => false,
            ]
          )
      )) {
        // Tallanto user not found in the list of telegram users or is blocked already. It's OK
        continue;
      }

      if (('Active' != $tallanto_user->getUserStatus()) ||
        ('Active' != $tallanto_user->getEmployeeStatus())
      ) {
        $db_user->setBlocked(true);
        $this->io->writeln(
          sprintf(
            'User "%s" blocked',
            trim($db_user->getLastName().' '.$db_user->getFirstName())
          )
        );
      }
    }
    $em->flush();
  }


}