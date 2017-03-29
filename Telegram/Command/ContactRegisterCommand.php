<?php
/**
 * Created by PhpStorm.
 * User: ant
 * Date: 16.03.2017
 * Time: 18:18
 */

namespace Kaula\TelegramBundle\Telegram\Command;


use Kaula\TelegramBundle\Entity\User;
use Tallanto\Api\Aggregator\ContactAggregator;

use Tallanto\Api\Provider\Http\Request;
use Tallanto\Api\Provider\Http\ServiceProvider;
use unreal4u\TelegramAPI\Telegram\Types\Contact AS TelegramContact;
use Tallanto\Api\Entity\Contact AS TallantoContact;


class ContactRegisterCommand extends RegisterCommand
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
    $contact_aggregator = $this->loadTallantoContacts($phone);
    if ($this->bindTallantoContact($contact_aggregator, $phone)) {
      return parent::registerUser($contact);
    }

    return false;
  }

  /**
   * Loads contact(s) from the Tallanto by phone.
   *
   * @param $phone
   * @return \Tallanto\Api\Aggregator\ContactAggregator
   */
  private function loadTallantoContacts($phone)
  {
    $c = $this->getBus()
      ->getBot()
      ->getContainer();

    // Create HTTP Request object for ServiceProvider
    $request = new Request();
    $request->setLogger($c->get('logger'))
      ->setUrl($c->getParameter('tallanto.url'))
      ->setMethod('/api/v1/contacts')
      ->setLogin($c->getParameter('tallanto.login'))
      ->setApiHash($c->getParameter('tallanto.token'));

    // Create ServiceProvider object
    $provider = new ServiceProvider($request);
    $provider->setLogger($c->get('logger'));
    /*if ($provider instanceof ExpandableInterface) {
      $provider->setExpand(TRUE);
    }*/

    // Create contacts aggregator
    $contacts_aggregator = new ContactAggregator($provider);
    $contacts_aggregator->search($phone);

    return $contacts_aggregator;
  }

  /**
   * Connects Telegram and Tallanto users.
   *
   * @param \Tallanto\Api\Aggregator\ContactAggregator $contact_aggregator
   * @param string $phone
   * @return bool
   */
  private function bindTallantoContact(
    ContactAggregator $contact_aggregator,
    $phone
  ) {
    if (0 == $contact_aggregator->count()) {

      // Contact not found in the Tallanto CRM, create it
      $tallanto_contact_id = $this->createTallantoContact(
        $contact_aggregator,
        $phone
      );

    } elseif ($contact_aggregator->count() > 1) {

      // Several contacts found, deny
      $this->replyWithMessage(
        'К сожалению, я нашёл несколько человек по указанному номеру телефона в базе данных ЦРМ.'.
        PHP_EOL.PHP_EOL.
        'Регистрация не завершена. Обратитесь к сотрудникам Школы, пожалуйста.'
      );

      return false;
    } else {

      // Single contact found in the Tallanto CRM, proceed
      $iterator = $contact_aggregator->getIterator();
      /** @var \Tallanto\Api\Entity\Contact $tallanto_contact */
      $tallanto_contact = $iterator->current();
      if (($tallanto_contact->getPhoneMobile() != $phone) &&
        ($tallanto_contact->getPhoneWork() != $phone)
      ) {
        throw new \RuntimeException(
          'Mobile and Work phones from Tallanto do not match Telegram contact phone '.
          $phone
        );
      }

      $tallanto_contact_id = $tallanto_contact->getId();
    }

    // Update user with Tallanto ID
    $this->updateTallantoUserInformation($tallanto_contact_id);

    return true;
  }

  /**
   * Updates user information in the database.
   *
   * @param string $tallanto_contact_id
   */
  protected function updateTallantoUserInformation($tallanto_contact_id)
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
    $user->setTallantoContactId($tallanto_contact_id);
    $em->persist($user);

    // Commit changes
    $em->flush();
  }

  /**
   * Creates contact in the Tallanto CRM.
   *
   * @param \Tallanto\Api\Aggregator\ContactAggregator $contact_aggregator
   * @param string $phone
   * @return string Returns Tallanto ID
   */
  protected function createTallantoContact(
    /** @noinspection PhpUnusedParameterInspection */ ContactAggregator $contact_aggregator,
    $phone
  ) {
/*    $c = $this->getBus()
      ->getBot()
      ->getContainer();*/
    $tu = $this->getUpdate()->message->from;

    // Create Contact object
    /** @noinspection PhpUnusedLocalVariableInspection */
    $contact = new TallantoContact(
      [
        'first_name' => $tu->first_name,
        'last_name' => $tu->last_name,
        'phone_mobile' => $phone,
      ]
    );
    // TODO implement ContactAggregator::add() method
    //$contact_aggregator->add($contact);

    return 'mock';
  }


}