<?php
/**
 * Created by PhpStorm.
 * User: ant
 * Date: 16.03.2017
 * Time: 18:18
 */

namespace Kaula\TelegramBundle\Telegram\Command;


use Kaula\TelegramBundle\Entity\User;
use RuntimeException;
use Tallanto\Api\Aggregator\UserAggregator;
use Tallanto\Api\Provider\Http\Request;
use Tallanto\Api\Provider\Http\ServiceProvider;
use unreal4u\TelegramAPI\Telegram\Types\Contact as TelegramContact;


class UserRegisterCommand extends RegisterCommand
{

  /**
   * Registers user in the database.
   *
   * @param \unreal4u\TelegramAPI\Telegram\Types\Contact $contact
   * @return bool
   */
  protected function registerUser(TelegramContact $contact)
  {
    $phone = $this->sanitizePhone($contact->phone_number);
    $user_aggregator = $this->loadTallantoUsers($phone);
    if ($this->bindTallantoUser($user_aggregator, $phone)) {
      return parent::registerUser($contact);
    }

    return false;
  }

  /**
   * Loads user(s) from the Tallanto by phone.
   *
   * @param $phone
   * @return \Tallanto\Api\Aggregator\UserAggregator
   */
  private function loadTallantoUsers($phone)
  {
    $c = $this->getBus()
      ->getBot()
      ->getContainer();

    // Create HTTP Request object for ServiceProvider
    $request = new Request();
    $request->setLogger($c->get('logger'))
      ->setUrl($c->getParameter('tallanto.url'))
      ->setMethod('/api/v1/users')
      ->setLogin($c->getParameter('tallanto.login'))
      ->setApiHash($c->getParameter('tallanto.token'));

    // Create ServiceProvider object
    $provider = new ServiceProvider($request);
    $provider->setLogger($c->get('logger'));

    // Create contacts aggregator
    $user_aggregator = new UserAggregator($provider);
    $user_aggregator->search($phone);

    return $user_aggregator;
  }

  /**
   * Connects Telegram and Tallanto users.
   *
   * @param \Tallanto\Api\Aggregator\UserAggregator $user_aggregator
   * @param string $phone
   * @return bool
   */
  private function bindTallantoUser(UserAggregator $user_aggregator, $phone)
  {
    if (0 == $user_aggregator->count()) {

      // User not found in the Tallanto CRM, deny
      $this->replyWithMessage(
        'К сожалению, я не нашёл сотрудника по указанному номеру телефона в базе данных ЦРМ.'.
        PHP_EOL.PHP_EOL.
        'Регистрация не завершена. Обратитесь в службу технической поддержки Школы, пожалуйста.'
      );

      return false;

    } elseif ($user_aggregator->count() > 1) {

      // Several contacts found, deny
      $this->replyWithMessage(
        'К сожалению, я нашёл несколько человек по указанному номеру телефона в базе данных ЦРМ.'.
        PHP_EOL.PHP_EOL.
        'Регистрация не завершена. Обратитесь в службу технической поддержки Школы, пожалуйста.'
      );

      return false;

    }

    // Single contact found in the Tallanto CRM, proceed
    $iterator = $user_aggregator->getIterator();
    /** @var \Tallanto\Api\Entity\User $tallanto_user */
    $tallanto_user = $iterator->current();
    if (($tallanto_user->getPhoneMobile() != $phone) &&
      ($tallanto_user->getPhoneWork() != $phone)
    ) {
      throw new RuntimeException(
        'Mobile and Work phones from Tallanto do not match Telegram contact phone '.
        $phone
      );
    }

    // Check if user is active
    if ('Active' != $tallanto_user->getUserStatus()) {
      $this->replyWithMessage(
        'К сожалению, вы не являетесь действующим сотрудником Школы.'.PHP_EOL.
        PHP_EOL.
        'Регистрация не завершена. Обратитесь в службу технической поддержки Школы, пожалуйста.'
      );

      return false;
    }

    // Update user with Tallanto ID
    $this->updateTallantoUserInformation($tallanto_user);

    return true;
  }

  /**
   * Updates user information in the database.
   *
   * @param \Tallanto\Api\Entity\User $tallanto_user
   */
  protected function updateTallantoUserInformation($tallanto_user)
  {
    $tu = $this->getUpdate()->message->from;
    $d = $this->getBus()
      ->getBot()
      ->getContainer()
      ->get('doctrine');
    $em = $d->getManager();

    // Find user object. If not found, create new
    /** @var User $user */
    $user = $d->getRepository('KaulaTelegramBundle:User')
      ->findOneBy(['telegram_id' => $tu->id]);
    if (!$user) {
      throw new \RuntimeException('User is expected to exist at this point.');
    }
    $user->setTallantoUserId($tallanto_user->getId())
      ->setExternalLastName($tallanto_user->getLastName())
      ->setExternalFirstName($tallanto_user->getFirstName());

    // Commit changes
    $em->flush();
  }

}