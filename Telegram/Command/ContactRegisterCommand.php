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


use Tallanto\ClientApiBundle\Api\Method\TallantoGetContactsMethod;
use Tallanto\ClientApiBundle\Api\TallantoApiClient;
use Tallanto\ClientApiBundle\Api\TallantoPump;
use unreal4u\TelegramAPI\Telegram\Types\Contact as TelegramContact;


class ContactRegisterCommand extends RegisterCommand
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
    $contacts = $this->downloadContacts($url, $login, $password, $phone);
    if ($this->bindTallantoContact($contacts, $phone)) {
      return parent::registerUser($contact);
    }

    return false;
  }

  /**
   * Downloads contacts from Tallanto matching $phone.
   *
   * @param string $url
   * @param string $login
   * @param string $password
   * @param string $phone
   * @return array Array of contact objects
   * @throws \Exception
   */
  private function downloadContacts($url, $login, $password, $phone)
  {
    $l = $this->getBus()
      ->getBot()
      ->getLogger();
    $l->debug(
      'About to download contacts from the Tallanto',
      [
        'url'   => $url,
        'login' => $login,
      ]
    );

    // Create Guzzle client
    $client = new Client(['base_uri' => $url]);
    // Create method
    $method = new TallantoGetContactsMethod($phone);
    $method->setLogin($login)
      ->setPassword($password);
    // Create Tallanto API client
    $api = new TallantoApiClient($client, $l);
    // Download all contacts matching phone
    $pump = new TallantoPump($api, $method);
    $items = $pump->suck();

    // Make array of objects
    $existingContacts = $method->getContacts($items);

    $l->debug(
      'Downloaded {contacts_count} items from the Tallanto',
      [
        'contacts_count'    => count($existingContacts),
        'existing_contacts' => substr(
          print_r($existingContacts, true),
          0,
          1024
        ),
      ]
    );

    // Convert array of array to array of contacts objects
    return $existingContacts;
  }

  /**
   * Connects Telegram user and Tallanto contact.
   *
   * @param array $contacts
   * @param string $phone
   * @return bool
   */
  private function bindTallantoContact(
    $contacts,
    $phone
  ) {
    if (0 == count($contacts)) {

      // Contact not found in the Tallanto CRM, deny
      $this->replyWithMessage(
        'К сожалению, я не нашёл людей по указанному номеру телефона в базе данных ЦРМ.'.
        PHP_EOL.PHP_EOL.
        'Регистрация не завершена. Обратитесь в администрацию Школы, пожалуйста.'
      );

      return false;

      // Contact not found in the Tallanto CRM, create it
      /*$tallanto_contact_id = $this->createTallantoContact(
        $contact_aggregator,
        $phone
      );*/

    } elseif (count($contacts) > 1) {

      // Several contacts found, deny
      $this->replyWithMessage(
        'К сожалению, я нашёл несколько человек по указанному номеру телефона в базе данных ЦРМ.'.
        PHP_EOL.PHP_EOL.
        'Регистрация не завершена. Обратитесь к сотрудникам Школы, пожалуйста.'
      );

      return false;
    }

    // Single contact found in the Tallanto CRM, proceed
    /** @var \Tallanto\Api\Entity\Contact $tallantoContact */
    $tallantoContact = reset($contacts);
    if (($tallantoContact->getPhoneMobile() != $phone) &&
      ($tallantoContact->getPhoneWork() != $phone)) {
      throw new RuntimeException(
        'Mobile and Work phones from Tallanto do not match Telegram contact phone '.
        $phone
      );
    }

    // Update User object
    $this->updateTallantoUserInformation($tallantoContact);

    return true;
  }

  /**
   * Updates user information in the database.
   *
   * @param \Tallanto\Api\Entity\Contact $tallantoContact
   */
  protected function updateTallantoUserInformation($tallantoContact)
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
    $user->setTallantoContactId($tallantoContact->getId())
      ->setExternalLastName($tallantoContact->getLastName())
      ->setExternalFirstName($tallantoContact->getFirstName());

    // Commit changes
    $em->flush();
  }
}