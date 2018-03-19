<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram\Command;


use Kettari\TelegramBundle\Telegram\CommandBusInterface;
use Kettari\TelegramBundle\Telegram\Communicator;
use Kettari\TelegramBundle\Telegram\CommunicatorInterface;
use Kettari\TelegramBundle\Telegram\PusherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Translation\TranslatorInterface;
use unreal4u\TelegramAPI\Telegram\Types\KeyboardButton;
use unreal4u\TelegramAPI\Telegram\Types\ReplyKeyboardMarkup;
use unreal4u\TelegramAPI\Telegram\Types\ReplyKeyboardRemove;
use unreal4u\TelegramAPI\Telegram\Types\Update;

class PushCommand extends AbstractCommand
{

  const BTN_CANCEL = 'command.button.cancel';
  static public $name = 'push';
  static public $description = 'command.push.description';
  static public $requiredPermissions = ['execute command push'];

  /**
   * @var \Symfony\Bridge\Doctrine\RegistryInterface
   */
  private $doctrine;

  /**
   * @var PusherInterface
   */
  private $pusher;

  /**
   * PushCommand constructor.
   *
   * @param \Psr\Log\LoggerInterface $logger
   * @param \Kettari\TelegramBundle\Telegram\CommandBusInterface $bus
   * @param \Symfony\Component\Translation\TranslatorInterface $translator
   * @param \Symfony\Bridge\Doctrine\RegistryInterface $doctrine
   * @param \Kettari\TelegramBundle\Telegram\PusherInterface $pusher
   * @param \Kettari\TelegramBundle\Telegram\CommunicatorInterface $communicator
   */
  public function __construct(
    LoggerInterface $logger,
    CommandBusInterface $bus,
    TranslatorInterface $translator,
    RegistryInterface $doctrine,
    PusherInterface $pusher,
    CommunicatorInterface $communicator
  ) {
    parent::__construct($logger, $bus, $translator, $communicator);
    $this->doctrine = $doctrine;
    $this->pusher = $pusher;
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
      $this->trans->trans('command.push.send_text')
    );
    $this->bus->createHook(
      $update,
      'kettari_telegram.command.push',
      'handlePushText'
    );
  }

  /**
   * Handles /push content text.
   *
   * @param \unreal4u\TelegramAPI\Telegram\Types\Update $update
   */
  public function handlePushText(Update $update)
  {
    $message = $update->message;
    if (is_null($message)) {
      return;
    }

    $push_text = $update->message->text;
    if (empty($push_text)) {
      $this->replyWithMessage(
        $update,
        $this->trans->trans('command.push.empty_text')
      );

      return;
    } elseif (mb_strlen($push_text) > 4096) {
      $this->replyWithMessage(
        $update,
        $this->trans->trans(
          'command.push.text_too_long',
          ['%text_length%' => mb_strlen($push_text)]
        )
      );

      return;
    }
    $this->replyWithMessage(
      $update,
      $this->trans->trans('command.push.choose_channel'),
      Communicator::PARSE_MODE_PLAIN,
      $this->getReplyKeyboardMarkup_Channels()
    );

    $this->bus->createHook(
      $update,
      'kettari_telegram.command.push',
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

    $notifications = $this->doctrine->getRepository(
      'KettariTelegramBundle:Notification'
    )
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
   * @param \unreal4u\TelegramAPI\Telegram\Types\Update $update
   * @param string $pushText
   */
  public function handlePushChannel(Update $update, string $pushText)
  {
    // Validate input data
    $message = $update->message;
    if (is_null($message)) {
      return;
    }
    // Empty text (should'n be but who knows)
    if (empty($pushText)) {
      $this->replyWithMessage(
        $update,
        $this->trans->trans('command.push.text_lost')
      );

      return;
    }
    $channelName = $update->message->text;
    // Empty channel name
    if (empty($channelName)) {
      $this->replyWithMessage(
        $update,
        $this->trans->trans('command.push.channel_name_empty')
      );

      return;
    }
    // Push cancelled
    if ($this->trans->trans(self::BTN_CANCEL) == $channelName) {
      $this->replyWithMessage(
        $update,
        $this->trans->trans('command.push.cancelled')
      );

      return;
    }

    // Verify channel name is good
    $notifications = $this->doctrine->getRepository(
      'KettariTelegramBundle:Notification'
    )
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
        $update,
        $this->trans->trans('command.push.channel_name_invalid'),
        Communicator::PARSE_MODE_PLAIN,
        $this->getReplyKeyboardMarkup_Channels()
      );
      $this->bus->createHook(
        $update,
        'kettari_telegram.command.push',
        'handlePushChannel',
        $pushText
      );

      return;
    }

    // Ask for confirmation
    $this->replyWithMessage(
      $update,
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
      $update,
      $pushText,
      Communicator::PARSE_MODE_HTML
    );
    $this->replyWithMessage(
      $update,
      $this->trans->trans(
        'command.push.confirmation_instructions'
      )
    );

    $this->bus->createHook(
      $update,
      'kettari_telegram.command.push',
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
   * @param \unreal4u\TelegramAPI\Telegram\Types\Update $update
   * @param string $pushInfo
   */
  public function handlePushFinalize(Update $update, $pushInfo)
  {
    $pushInfo = unserialize($pushInfo);
    if (!isset($pushInfo['push_text']) || empty($pushInfo['push_text']) ||
      !isset($pushInfo['notification']) || empty($pushInfo['notification'])) {
      $this->replyWithMessage(
        $update,
        $this->trans->trans('command.push.something_went_wrong')
      );

      return;
    }

    // Verify confirmation
    $confirmationText = $update->message->text;
    if ($this->trans->trans('command.push.confirmation') != $confirmationText) {
      $this->replyWithMessage(
        $update,
        $this->trans->trans('command.push.cancelled')
      );

      return;
    }

    // Push the message
    $this->pusher->pushNotification(
      $pushInfo['notification'],
      $pushInfo['push_text'],
      Communicator::PARSE_MODE_HTML
    );
    $this->pusher->bumpQueue();

    // Report job done
    $this->replyWithMessage(
      $update,
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