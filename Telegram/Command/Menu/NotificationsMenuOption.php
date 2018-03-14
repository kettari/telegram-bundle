<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram\Command\Menu;


use Kettari\TelegramBundle\Telegram\Command\ReplyWithTrait;
use Kettari\TelegramBundle\Telegram\CommandBusInterface;
use Kettari\TelegramBundle\Telegram\Communicator;
use Kettari\TelegramBundle\Telegram\TelegramObjectsRetrieverTrait;
use unreal4u\TelegramAPI\Telegram\Types\Inline\Keyboard\Button;
use unreal4u\TelegramAPI\Telegram\Types\Inline\Keyboard\Markup;
use unreal4u\TelegramAPI\Telegram\Types\Update;

class NotificationsMenuOption extends AbstractMenuOption
{
  use TelegramObjectsRetrieverTrait, ReplyWithTrait;

  const MARK_V = "\xE2\x9C\x85";
  const MARK_X = "\xE2\x9D\x8C";

  /**
   * @param \Kettari\TelegramBundle\Telegram\CommandBusInterface $bus
   * @param \unreal4u\TelegramAPI\Telegram\Types\Update $update
   */
  public function __construct(CommandBusInterface $bus, Update $update)
  {
    parent::__construct($bus, $update);
    $this->caption = 'menu.notifications.button_caption';
    $this->callbackId = 'menu.notifications';
  }

  /**
   * @inheritDoc
   */
  public function click(): bool
  {
    $this->logger->debug('Clicking notifications option');

    // Reply with notifications set
    $this->replyWithMessage(
      $this->trans->trans('menu.notifications.select'),
      Communicator::PARSE_MODE_PLAIN,
      $this->getReplyKeyboardMarkup_Notifications()
    );
    // Mark request as handled to prevent home menu
    $this->keeper->setRequestHandled(true);

    $this->logger->info('Clicked notifications option');

    return true;
  }

  /**
   * Returns reply markup for notifications option.
   *
   * @return \unreal4u\TelegramAPI\Telegram\Types\Inline\Keyboard\Markup
   */
  private function getReplyKeyboardMarkup_Notifications()
  {
    // Load user's permissions and notifications
    $userPermissions = $this->bus->getUserHq()
      ->getUserPermissions();
    $userNotifications = $this->bus->getUserHq()
      ->getUserNotifications();

    // Load notifications
    /** @var \Kettari\TelegramBundle\Entity\Notification $notifications */
    $notifications = $this->bus->getDoctrine()
      ->getRepository('KettariTelegramBundle:Notification')
      ->findAllOrdered();
    // Check if user has required for each notification permission
    $inlineKeyboard = new Markup();
    /** @var \Kettari\TelegramBundle\Entity\Notification $notificationItem */
    foreach ($notifications as $notificationItem) {
      $row = [];
      if ($userPermissions->contains($notificationItem->getPermission())) {

        // Does user have this notification enabled?
        $mark = ($userNotifications->contains(
          $notificationItem
        )) ? self::MARK_V : self::MARK_X;

        $inlineKeyboardButton = new Button();
        $inlineKeyboardButton->text = $mark.' '.
          $this->trans->trans($notificationItem->getTitle());
        $inlineKeyboardButton->callback_data = $notificationItem->getName();
        $row[] = $inlineKeyboardButton;
      }
      if (count($row)) {
        $inlineKeyboard->inline_keyboard[] = $row;
      }
    }

    return $inlineKeyboard;
  }

  /**
   * @inheritDoc
   */
  public function handler($parameter)
  {
    $this->logger->debug('Handling notifications option');

    $cq = $this->update->callback_query;
    if (is_null($cq) && !is_null($this->update->message)) {
      $this->replyWithMessage($this->trans->trans('command.cancelled'));

      return;
    }
    if (is_null($cq) || is_null($cq->message)) {
      return;
    }

    // Load user's permissions and notifications
    /** @var \Kettari\TelegramBundle\Entity\User $user */
    $user = $this->bus->getUserHq()
      ->getCurrentUser();
    $userPermissions = $this->bus->getUserHq()
      ->getUserPermissions();
    $userNotifications = $this->bus->getUserHq()
      ->getUserNotifications();

    // Load notification
    /** @var \Kettari\TelegramBundle\Entity\Notification $notification */
    $notification = $this->bus->getDoctrine()
      ->getRepository('KettariTelegramBundle:Notification')
      ->findOneByName($cq->data);
    if (is_null($notification)) {
      return;
    }
    // Check user permission
    if (!$userPermissions->contains($notification->getPermission())) {
      return;
    }

    // Enable notification or disable it
    if ($userNotifications->contains($notification)) {
      $user->removeNotification($notification);
    } else {
      $user->addNotification($notification);
    }
    // Save changes
    $this->bus->getDoctrine()
      ->getManager()
      ->persist($user);
    $this->bus->getDoctrine()
      ->getManager()
      ->flush();

    // Update keyboard
    $this->comm->editMessageReplyMarkup(
      $cq->message->chat->id,
      $cq->message->message_id,
      null,
      $this->getReplyKeyboardMarkup_Notifications()
    );
    // Answer callback
    $this->comm->answerCallbackQuery(
      $cq->id,
      $this->trans->trans('menu.notifications.settings_saved')
    );
    // Register hook
    $this->hookMySelf();
    // Mark request as handled to prevent home menu
    $this->keeper->setRequestHandled(true);

    $this->logger->debug('Notifications option handled');
  }
}