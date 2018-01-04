<?php
/**
 * Created by PhpStorm.
 * User: ant
 * Date: 16.03.2017
 * Time: 18:18
 */

namespace Kaula\TelegramBundle\Telegram\Command;


use GuzzleHttp\Client;
use Kaula\TelegramBundle\Entity\User;
use RuntimeException;
use Tallanto\ClientApiBundle\Api\Method\TallantoGetUsersMethod;
use Tallanto\ClientApiBundle\Api\TallantoApiClient;
use Tallanto\ClientApiBundle\Api\TallantoPump;
use unreal4u\TelegramAPI\Telegram\Types\Contact as TelegramContact;


class UserRegisterCommand extends RegisterCommand
{

  /**
   * Registers user in the database.
   *
   * @param \unreal4u\TelegramAPI\Telegram\Types\Contact $contact
   * @return bool
   * @throws \Exception
   */
  protected function registerUser(TelegramContact $contact)
  {
    $c = $this->getBus()
      ->getBot()
      ->getContainer();
    $url = $c->getParameter('tallanto.url');
    $login = $c->getParameter('tallanto.login');
    $password = $c->getParameter('tallanto.token');
    $phone = $this->sanitizePhone($contact->phone_number);
    $users = $this->downloadUsers($url, $login, $password, $phone);
    if ($this->bindTallantoUser($users, $phone)) {
      return parent::registerUser($contact);
    }

    return false;
  }

  /**
   * Downloads users from Tallanto matching $phone.
   *
   * @param string $url
   * @param string $login
   * @param string $password
   * @param string $phone
   * @return array Array of contact objects
   * @throws \Exception
   */
  private function downloadUsers($url, $login, $password, $phone)
  {
    $l = $this->getBus()
      ->getBot()
      ->getLogger();
    $l->debug(
      'About to download users from the Tallanto',
      [
        'url'   => $url,
        'login' => $login,
      ]
    );

    // Create Guzzle client
    $client = new Client(['base_uri' => $url]);
    // Create method
    $method = new TallantoGetUsersMethod($phone);
    $method->setLogin($login)
      ->setPassword($password);
    // Create Tallanto API client
    $api = new TallantoApiClient($client, $l);
    // Download all users matching phone
    $pump = new TallantoPump($api, $method);
    $items = $pump->suck();

    // Make array of objects
    $existingUsers = $method->getUsers($items);

    $l->debug(
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
   * Connects Telegram and Tallanto users.
   *
   * @param array $users
   * @param string $phone
   * @return bool
   */
  private function bindTallantoUser($users, $phone)
  {
    if (0 == count($users)) {

      // User not found in the Tallanto CRM, deny
      $this->replyWithMessage(
        'К сожалению, я не нашёл сотрудника по указанному номеру телефона в базе данных ЦРМ.'.
        PHP_EOL.PHP_EOL.
        'Регистрация не завершена. Обратитесь в службу технической поддержки Школы, пожалуйста.'
      );

      return false;

    } elseif (count($users) > 1) {

      // Several contacts found, deny
      $this->replyWithMessage(
        'К сожалению, я нашёл несколько человек по указанному номеру телефона в базе данных ЦРМ.'.
        PHP_EOL.PHP_EOL.
        'Регистрация не завершена. Обратитесь в службу технической поддержки Школы, пожалуйста.'
      );

      return false;

    }

    // Single contact found in the Tallanto CRM, proceed
    /** @var \Tallanto\Api\Entity\User $tallantoUser */
    $tallantoUser = reset($contacts);
    if (($tallantoUser->getPhoneMobile() != $phone) &&
      ($tallantoUser->getPhoneWork() != $phone)) {
      throw new RuntimeException(
        'Mobile and Work phones from Tallanto do not match Telegram contact phone '.
        $phone
      );
    }

    // Check if user is active
    if ('Active' != $tallantoUser->getUserStatus()) {
      $this->replyWithMessage(
        'К сожалению, вы не являетесь действующим сотрудником Школы.'.PHP_EOL.
        PHP_EOL.
        'Регистрация не завершена. Обратитесь в службу технической поддержки Школы, пожалуйста.'
      );

      return false;
    }

    // Update User object
    $this->updateTallantoUserInformation($tallantoUser);

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