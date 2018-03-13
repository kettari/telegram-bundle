<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram\Command;


use Kettari\TelegramBundle\Telegram\Communicator;
use unreal4u\TelegramAPI\Telegram\Types\KeyboardButton;
use unreal4u\TelegramAPI\Telegram\Types\ReplyKeyboardMarkup;
use unreal4u\TelegramAPI\Telegram\Types\ReplyKeyboardRemove;

class PushCommand extends AbstractCommand
{

  const BTN_CANCEL = 'command.button.cancel';
  static public $name = 'push';
  static public $description = 'command.push.description';
  static public $requiredPermissions = ['execute command push'];

  /**
   * Executes command.
   */
  public function execute()
  {
    // This command is available only in private chat
    if ('private' != $this->update->message->chat->type) {
      $this->replyWithMessage(
        $this->trans->trans('command.private_only')
      );

      return;
    }

    $this->replyWithMessage($this->trans->trans('command.push.send_text'));
    $this->bus->createHook($this->update, get_class($this), 'handlePushText');
  }

  /**
   * Handles /push content text.
   */
  public function handlePushText()
  {
    $message = $this->update->message;
    if (is_null($message)) {
      return;
    }

    $push_text = $this->update->message->text;
    if (empty($push_text)) {
      $this->replyWithMessage($this->trans->trans('command.push.empty_text'));

      return;
    } elseif (mb_strlen($push_text) > 4096) {
      $this->replyWithMessage(
        $this->trans->trans(
          'command.push.text_too_long',
          ['%text_length%' => mb_strlen($push_text)]
        )
      );

      return;
    }
    $this->replyWithMessage(
      $this->trans->trans('command.push.choose_channel'),
      Communicator::PARSE_MODE_PLAIN,
      $this->getReplyKeyboardMarkup_Channels()
    );

    $this->bus->createHook(
      $this->update,
      get_class($this),
      'handlePushChannel',
      $push_text
    );
  }

  /**
   * Returns reply markup object.
   *
   * @return \unreal4u\TelegramAPI\Telegram\Types\ReplyKeyboardMarkup
   */
  private function getReplyKeyboardMarkup_Channels()
  {
    // Keyboard
    $replyMarkup = new ReplyKeyboardMarkup();
    $replyMarkup->one_time_keyboard = true;
    $replyMarkup->resize_keyboard = true;

    $notifications = $this->bus->getDoctrine()
      ->getRepository('KettariTelegramBundle:Notification')
      ->findAllOrdered();
    /** @var \Kettari\TelegramBundle\Entity\Notification $notificationItem */
    foreach ($notifications as $notificationItem) {
      $notificationBtn = new KeyboardButton();
      $notificationBtn->text = $notificationItem->getTitle();
      $replyMarkup->keyboard[][] = $notificationBtn;
    }

    // Cancel button
    $cancelBtn = new KeyboardButton();
    $cancelBtn->text = $this->trans->trans(self::BTN_CANCEL);

    $replyMarkup->keyboard[][] = $cancelBtn;

    return $replyMarkup;
  }

  /**
   * Handles /push channel selection.
   *
   * @param string $pushText
   */
  public function handlePushChannel(string $pushText)
  {
    // Validate input data
    $message = $this->update->message;
    if (is_null($message)) {
      return;
    }
    // Empty text (should'n be but who knows)
    if (empty($pushText)) {
      $this->replyWithMessage($this->trans->trans('command.push.text_lost'));

      return;
    }
    $channelName = $this->update->message->text;
    // Empty channel name
    if (empty($channelName)) {
      $this->replyWithMessage(
        $this->trans->trans('command.push.channel_name_empty')
      );

      return;
    }
    // Push cancelled
    if ($this->trans->trans(self::BTN_CANCEL) == $channelName) {
      $this->replyWithMessage($this->trans->trans('command.push.cancelled'));

      return;
    }

    // Verify channel name is good
    $notifications = $this->bus->getDoctrine()
      ->getRepository('KettariTelegramBundle:Notification')
      ->findAll();
    $channelSelected = null;
    /** @var \Kettari\TelegramBundle\Entity\Notification $notificationItem */
    foreach ($notifications as $notificationItem) {
      if ($channelName == $notificationItem->getTitle()) {
        $channelSelected = $notificationItem;
        break;
      }
    }
    // Invalid notification channel name
    if (is_null($channelSelected)) {
      $this->replyWithMessage(
        $this->trans->trans('command.push.channel_name_invalid'),
        Communicator::PARSE_MODE_PLAIN,
        $this->getReplyKeyboardMarkup_Channels()
      );
      $this->bus->createHook(
        $this->update,
        get_class($this),
        'handlePushChannel',
        $pushText
      );

      return;
    }

    // Ask for confirmation
    $this->replyWithMessage(
      $this->trans->trans(
        'command.push.confirmation_message',
        [
          '%channel_caption%' => $channelName,
          '%channel_name%'    => $channelSelected->getName(),
          '%text_length%'     => mb_strlen($pushText),
        ]
      ),
      Communicator::PARSE_MODE_HTML
    );
    $this->replyWithMessage(
      $pushText,
      Communicator::PARSE_MODE_HTML
    );
    $this->replyWithMessage(
      $this->trans->trans(
        'command.push.confirmation_instructions'
      )
    );

    $this->bus->createHook(
      $this->update,
      get_class($this),
      'handlePushFinalize',
      serialize(
        [
          'push_text'    => $pushText,
          'notification' => $channelSelected->getName(),
        ]
      )
    );
  }

  /**
   * Handles /push final confirmation and sends push.
   *
   * @param string $pushInfo
   */
  public function handlePushFinalize($pushInfo)
  {
    $pushInfo = unserialize($pushInfo);
    if (!isset($pushInfo['push_text']) || empty($pushInfo['push_text']) ||
      !isset($pushInfo['notification']) || empty($pushInfo['notification'])) {
      $this->replyWithMessage(
        $this->trans->trans('command.push.something_went_wrong')
      );

      return;
    }

    // Verify confirmation
    $confirmationText = $this->update->message->text;
    if ($this->trans->trans('command.push.confirmation') !=
      $confirmationText) {
      $this->replyWithMessage($this->trans->trans('command.push.cancelled'));

      return;
    }

    // Push the message
    $this->bus->getPusher()
      ->pushNotification(
        $pushInfo['notification'],
        $pushInfo['push_text'],
        Communicator::PARSE_MODE_HTML
      );
    $this->bus->getPusher()
      ->bumpQueue();

    // Report job done
    $this->replyWithMessage(
      $this->trans->trans(
        'command.push.notification_sent',
        [
          '%channel_caption%' => $pushInfo['notification'],
          '%text_length%'     => mb_strlen($pushInfo['push_text']),
        ]
      ),
      Communicator::PARSE_MODE_PLAIN,
      new ReplyKeyboardRemove()
    );
  }

}