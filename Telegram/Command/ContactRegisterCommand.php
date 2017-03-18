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
use unreal4u\TelegramAPI\Telegram\Types\Contact;


class ContactRegisterCommand extends RegisterCommand {

  /**
   * Registers user in the database.
   *
   * @param \unreal4u\TelegramAPI\Telegram\Types\Contact $contact
   * @return bool
   */
  protected function registerUser(Contact $contact) {
    if (!parent::registerUser($contact)) {
      return FALSE;
    }

    $contact_aggregator = $this->loadTallantoContacts($contact->phone_number);

    return $this->bindTallantoContact($contact_aggregator,
      $contact->phone_number);
  }

  /**
   * Loads contact(s) from the Tallanto by phone.
   *
   * @param $phone
   * @return \Tallanto\Api\Aggregator\ContactAggregator
   */
  private function loadTallantoContacts($phone) {
    $c = $this->getBus()
      ->getBot()
      ->getContainer();

    // Create HTTP Request object for ServiceProvider
    $request = new Request();
    $tallanto_url = sprintf('http://%s%s', $c->getParameter('tallanto.host'),
      ($c->getParameter('kernel.environment') == 'dev') ? '/app_dev.php' : '');
    $request->setLogger($c->get('logger'))
      ->setUrl($tallanto_url)
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
  private function bindTallantoContact(ContactAggregator $contact_aggregator, $phone) {
    if (0 == $contact_aggregator->count()) {
      $this->replyWithMessage('К сожалению, я не смог найти вас по номеру телефона в базе данных ЦРМ.'.
        PHP_EOL.PHP_EOL.
        'Регистрация не завершена. Обратитесь к сотрудникам Школы, пожалуйста.');

      return FALSE;
    } elseif ($contact_aggregator->count() > 1) {
      $this->replyWithMessage('К сожалению, я нашёл несколько человек по указанному номеру телефона в базе данных ЦРМ.'.
        PHP_EOL.PHP_EOL.
        'Регистрация не завершена. Обратитесь к сотрудникам Школы, пожалуйста.');

      return FALSE;
    }
    $iterator = $contact_aggregator->getIterator();
    /** @var \Tallanto\Api\Entity\Contact $tallanto_contact */
    $tallanto_contact = $iterator->current();
    if (($tallanto_contact->getPhoneMobile() != $phone) &&
      ($tallanto_contact->getPhoneWork() != $phone)
    ) {
      throw new \RuntimeException('Mobile and Work phones from Tallanto do not match Telegram contact phone '.
        $phone);
    }

    // Fill user with Tallanto ID and update
    $user = new User();
    $user->setTallantoContactId($tallanto_contact->getId());
    $this->updateUserInformation($user);

    return TRUE;
  }


}