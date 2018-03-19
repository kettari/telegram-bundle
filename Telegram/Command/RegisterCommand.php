<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram\Command;


use Kettari\TelegramBundle\Entity\User;
use Kettari\TelegramBundle\Exception\TelegramBundleException;
use Kettari\TelegramBundle\Telegram\CommandBusInterface;
use Kettari\TelegramBundle\Telegram\Communicator;
use Kettari\TelegramBundle\Telegram\CommunicatorInterface;
use Kettari\TelegramBundle\Telegram\Event\UserRegisteredEvent;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Translation\TranslatorInterface;
use unreal4u\TelegramAPI\Telegram\Types\Contact;
use unreal4u\TelegramAPI\Telegram\Types\KeyboardButton;
use unreal4u\TelegramAPI\Telegram\Types\ReplyKeyboardMarkup;
use unreal4u\TelegramAPI\Telegram\Types\ReplyKeyboardRemove;
use unreal4u\TelegramAPI\Telegram\Types\Update;

class RegisterCommand extends AbstractCommand
{

  const BTN_PHONE = 'command.register.send_phone';
  const BTN_CANCEL = 'command.button.cancel';
  const NOTIFICATION_NEW_REGISTER = 'new-register';

  static public $name = 'register';
  static public $description = 'command.register.description';
  static public $requiredPermissions = ['execute command register'];
  static public $declaredNotifications = [self::NOTIFICATION_NEW_REGISTER];

  /**
   * @var \Symfony\Bridge\Doctrine\RegistryInterface
   */
  private $doctrine;

  /**
   * @var EventDispatcherInterface
   */
  private $dispatcher;

  /**
   * RegisterCommand constructor.
   *
   * @param \Psr\Log\LoggerInterface $logger
   * @param \Kettari\TelegramBundle\Telegram\CommandBusInterface $bus
   * @param \Symfony\Component\Translation\TranslatorInterface $translator
   * @param \Symfony\Bridge\Doctrine\RegistryInterface $doctrine
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher
   * @param \Kettari\TelegramBundle\Telegram\CommunicatorInterface $communicator
   */
  public function __construct(
    LoggerInterface $logger,
    CommandBusInterface $bus,
    TranslatorInterface $translator,
    RegistryInterface $doctrine,
    EventDispatcherInterface $dispatcher,
    CommunicatorInterface $communicator
  ) {
    parent::__construct($logger, $bus, $translator, $communicator);
    $this->doctrine = $doctrine;
    $this->dispatcher = $dispatcher;
  }

  /**
   * @inheritdoc
   */
  public function execute(Update $update, string $parameter = '')
  {
    // This command is available only in private chat
    if ('private' != $update->message->chat->type) {
      $this->replyWithMessage(
        $update,
        $this->trans->trans('command.private_only')
      );

      return;
    }

    $this->replyWithMessage(
      $update,
      $this->trans->trans('command.register.send_instruction'),
      Communicator::PARSE_MODE_PLAIN,
      $this->getReplyKeyboardMarkup()
    );

    // Register the hook so when user will send information, we will be notified.
    $this->bus->createHook(
      $update,
      'kettari_telegram.command.register',
      'handleContact'
    );
  }

  /**
   * Returns reply markup object.
   *
   * @return ReplyKeyboardMarkup
   */
  private function getReplyKeyboardMarkup()
  {
    // Phone button
    $phoneBtn = new KeyboardButton();
    $phoneBtn->request_contact = true;
    $phoneBtn->text = $this->trans->trans(self::BTN_PHONE);

    // Cancel button
    $cancelBtn = new KeyboardButton();
    $cancelBtn->text = $this->trans->trans(self::BTN_CANCEL);

    // Keyboard
    $replyMarkup = new ReplyKeyboardMarkup();
    $replyMarkup->one_time_keyboard = true;
    $replyMarkup->resize_keyboard = true;
    $replyMarkup->keyboard[][] = $phoneBtn;
    $replyMarkup->keyboard[][] = $cancelBtn;

    return $replyMarkup;
  }

  /**
   * Handles update with (possibly) contact from the user.
   *
   * @param \unreal4u\TelegramAPI\Telegram\Types\Update $update
   */
  public function handleContact(Update $update)
  {
    $message = $update->message;
    if (!is_null($message->contact)) {
      // Check if user sent his contact
      if ($message->contact->user_id != $message->from->id) {
        $this->replyWithMessage(
          $update,
          $this->trans->trans('command.register.not_your_phone'),
          Communicator::PARSE_MODE_PLAIN,
          new ReplyKeyboardRemove()
        );

        return;
      }

      // Seems to be OK
      if ($this->registerUser($update, $message->contact)) {
        $this->replyWithMessage(
          $update,
          $this->trans->trans(
            'command.register.success',
            ['%phone_number%' => $message->contact->phone_number]
          )
        );
      }
    } elseif ($this->trans->trans(self::BTN_CANCEL) == $message->text) {
      $this->replyWithMessage(
        $update,
        $this->trans->trans('command.cancelled'),
        Communicator::PARSE_MODE_PLAIN,
        new ReplyKeyboardRemove()
      );
    } else {

      // If user sent us numbers, help him to understand he should send a contact,
      // not type characters.
      $sanitizedText = $this->sanitizePhone(
        $message->text ? $message->text : ''
      );
      if (mb_strlen($sanitizedText) > 7) {
        $this->replyWithMessage(
          $update,
          $this->trans->trans('command.register.not_contact'),
          Communicator::PARSE_MODE_HTML,
          $this->getReplyKeyboardMarkup()
        );

        // Register the hook so when user will send information, we will be notified.
        $this->bus->createHook(
          $update,
          get_class($this),
          'handleContact'
        );

      } else {
        $this->replyWithMessage(
          $update,
          $this->trans->trans('command.register.invalid_number'),
          Communicator::PARSE_MODE_PLAIN,
          new ReplyKeyboardRemove()
        );
      }
    }
  }

  /**
   * Registers user in the database.
   *
   * @param \unreal4u\TelegramAPI\Telegram\Types\Update $update
   * @param \unreal4u\TelegramAPI\Telegram\Types\Contact $contact
   * @return bool
   */
  protected function registerUser(Update $update, Contact $contact)
  {
    $telegramUser = $update->message->from;

    // Find role object
    /** @var \Kettari\TelegramBundle\Entity\Role $registeredRole */
    $registeredRole = $this->doctrine->getRepository(
      'KettariTelegramBundle:Role'
    )
      ->findOneByName('registered');
    if (is_null($registeredRole)) {
      throw new \LogicException('Role for registered users not found');
    }

    // Find user object. If not found, create new
    /** @var User $userEntity */
    $userEntity = $this->doctrine->getRepository('KettariTelegramBundle:User')
      ->findOneByTelegramId($telegramUser->id);
    if (!$userEntity) {
      throw new TelegramBundleException(
        sprintf(
          'Unable to register user ID=%s: entity expected in the database and not found.',
          $telegramUser->id
        )
      );
    }
    // Update information
    $phone = $this->sanitizePhone(
      $contact->phone_number ? $contact->phone_number : ''
    );
    $userEntity->setPhone($phone);
    if (!$userEntity->hasRole($registeredRole)) {
      $userEntity->addRole($registeredRole);
    }

    // Load default notifications and assign them to the user
    $defaultNotifications = $this->getDefaultNotifications();
    $this->assignDefaultNotifications($userEntity, $defaultNotifications);

    // Commit changes
    $this->doctrine->getManager()
      ->persist($userEntity);
    $this->doctrine->getManager()
      ->flush();

    // Dispatch new registration event
    $this->dispatchNewRegistration($update, $userEntity);

    return true;
  }

  /**
   * Sanitize phone and return pure numbers.
   *
   * @param string $phone
   * @return string
   */
  protected function sanitizePhone(string $phone): string
  {
    // Remove all chars except numbers
    $needle = preg_replace('/[^0-9]/', '', $phone);
    // Replace leading 8 with 7
    if ('8' == substr($needle, 0, 1)) {
      $needle = '7'.substr($needle, 1);
    }
    // Add missing digit
    if (strlen($needle) == 10) {
      $needle = '7'.$needle;
    }

    return $needle;
  }

  /**
   * Returns array with notifications for anonymous users.
   *
   * @return array
   */
  private function getDefaultNotifications()
  {
    $notifications = $this->doctrine->getRepository(
      'KettariTelegramBundle:Notification'
    )
      ->findDefault();
    if (0 == count($notifications)) {
      // No error, just no default notifications defined
      return [];
    }

    return $notifications;
  }

  /**
   * Assigns notifications to the User.
   *
   * @param \Kettari\TelegramBundle\Entity\User $user
   * @param array $notifications
   */
  private function assignDefaultNotifications(
    User $user,
    array $notifications
  ) {
    if (count($notifications)) {
      // Load all current notifications assigned to user
      $currentNotifications = $user->getNotifications();
      /** @var \Kettari\TelegramBundle\Entity\Notification $newNotification */
      foreach ($notifications as $newNotification) {
        if (!$currentNotifications->contains($newNotification)) {
          $user->addNotification($newNotification);
          $this->doctrine->getManager()
            ->persist($user);
        }
      }
    }
  }

  /**
   * Dispatches command is unknown.
   *
   * @param Update $update
   * @param \Kettari\TelegramBundle\Entity\User $userEntity
   */
  private function dispatchNewRegistration(
    Update $update,
    User $userEntity
  ) {
    // Dispatch new registration event
    $userRegisteredEvent = new UserRegisteredEvent(
      $update, $userEntity
    );
    $this->dispatcher->dispatch(
      UserRegisteredEvent::NAME,
      $userRegisteredEvent
    );
  }

}