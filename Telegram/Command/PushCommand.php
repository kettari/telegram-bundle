<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram\Command;


use unreal4u\TelegramAPI\Telegram\Types\KeyboardButton;
use unreal4u\TelegramAPI\Telegram\Types\ReplyKeyboardMarkup;
use unreal4u\TelegramAPI\Telegram\Types\ReplyKeyboardRemove;

class PushCommand extends AbstractCommand
{

  const BTN_CANCEL = 'Отмена рассылки';
  static public $name = 'push';
  static public $description = 'Отправить широковещательное уведомление';
  static public $requiredPermissions = ['execute command push'];

  /**
   * Executes command.
   */
  public function execute()
  {
    if ('private' == $this->update->message->chat->type) {
      $this->replyWithMessage('Отправьте мне текст для рассылки, пожалуйста.');
      $this->bus->createHook(
        $this->update,
        get_class($this),
        'handlePushText'
      );
    } else {
      $this->replyWithMessage(
        'Эта команда работает только в личной переписке с ботом. В общем канале создание рассылки невозможно.'
      );
    }
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
      $this->replyWithMessage(
        'Вы отправили мне пустое сообщение. Начните заново /push'
      );

      return;
    } elseif (mb_strlen($push_text) > 4096) {
      $this->replyWithMessage(
        sprintf(
          'Текст длиннее предельно допустимой длины в 4096 символов: %d символов. Начните заново /push',
          mb_strlen($push_text)
        )
      );

      return;
    }
    $this->replyWithMessage(
      'Текст получил.'.PHP_EOL.PHP_EOL.'В какой канал высылаем сообщение?',
      '',
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
    $cancelBtn->text = self::BTN_CANCEL;

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
    $message = $this->update->message;
    if (is_null($message)) {
      return;
    }

    if (empty($pushText)) {
      $this->replyWithMessage(
        'Текст для рассылки потерялся. Начните заново /push'
      );

      return;
    }

    $channelName = $this->update->message->text;
    if (empty($channelName)) {
      $this->replyWithMessage(
        'Название канала рассылки пустое. Начните заново /push'
      );

      return;
    } elseif (self::BTN_CANCEL == $channelName) {
      $this->replyWithMessage(
        'Рассылка отменена.'
      );

      return;
    }

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
    if (is_null($channelSelected)) {
      $this->replyWithMessage(
        'Некорректное название канала рассылки. Выберите из списка ещё раз.',
        '',
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

    $this->replyWithMessage(
      sprintf(
        'Подтвердите корректность указанной ниже информации.'.PHP_EOL.PHP_EOL.
        '<b>Канал:</b> %s (%s)'.PHP_EOL.'<b>Длина:</b> %d символов'.PHP_EOL.
        '<b>Текст:</b>',
        $channelName,
        $channelSelected->getName(),
        mb_strlen($pushText)
      ),
      'HTML'
    );
    $this->replyWithMessage(
      $pushText,
      'HTML'
    );
    $this->replyWithMessage(
      'Если всё правильно, напишите мне ЗАГЛАВНЫМИ БУКВАМИ текст «рассылку подтверждаю». Любой другой текст отменяет рассылку.'
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
        'Что-то пошло не так. Начните заново /push'
      );

      return;
    }

    $confirmationText = $this->update->message->text;
    if ("РАССЫЛКУ ПОДТВЕРЖДАЮ" != $confirmationText) {
      $this->replyWithMessage(
        'Рассылка не подтверждена и отменена.'
      );

      return;
    }

    // Push the message
    $this->bus->getPusher()
      ->pushNotification(
        $pushInfo['notification'],
        $pushInfo['push_text'],
        'HTML'
      );
    $this->bus->getPusher()
      ->bumpQueue();

    // Report job done
    $this->replyWithMessage(
      sprintf(
        'Рассылка в канал «%s» подтверждена и выслана (%d символов).',
        $pushInfo['notification'],
        mb_strlen($pushInfo['push_text'])
      ),
      null,
      new ReplyKeyboardRemove()
    );
  }

}