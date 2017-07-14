<?php
/**
 * Created by PhpStorm.
 * User: ant
 * Date: 16.03.2017
 * Time: 18:18
 */

namespace Kaula\TelegramBundle\Telegram\Command;


use unreal4u\TelegramAPI\Telegram\Types\KeyboardButton;
use unreal4u\TelegramAPI\Telegram\Types\ReplyKeyboardMarkup;
use unreal4u\TelegramAPI\Telegram\Types\ReplyKeyboardRemove;

class PushCommand extends AbstractCommand
{

  const BTN_CANCEL = 'Отмена рассылки';
  static public $name = 'push';
  static public $description = 'Отправить широковещательное уведомление';
  static public $required_permissions = ['execute command push'];

  /**
   * Executes command.
   */
  public function execute()
  {
    if ('private' == $this->getUpdate()->message->chat->type) {
      $this->replyWithMessage('Отправьте мне текст для рассылки, пожалуйста.');
      $this->getBus()
        ->getHooker()
        ->createHook(
          $this->getUpdate(),
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
    $message = $this->getUpdate()->message;
    if (is_null($message)) {
      return;
    }

    $push_text = $this->getUpdate()->message->text;
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

    $this->getBus()
      ->getHooker()
      ->createHook(
        $this->getUpdate(),
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
    $reply_markup = new ReplyKeyboardMarkup();
    $reply_markup->one_time_keyboard = true;
    $reply_markup->resize_keyboard = true;

    $d = $this->getBus()
      ->getBot()
      ->getContainer()
      ->get('doctrine');
    $notifications = $d->getRepository('KaulaTelegramBundle:Notification')
      ->findBy([], ['order' => 'ASC']);
    /** @var \Kaula\TelegramBundle\Entity\Notification $notification_item */
    foreach ($notifications as $notification_item) {
      $notification_btn = new KeyboardButton();
      $notification_btn->text = $notification_item->getTitle();
      $reply_markup->keyboard[][] = $notification_btn;
    }

    // Cancel button
    $cancel_btn = new KeyboardButton();
    $cancel_btn->text = self::BTN_CANCEL;

    $reply_markup->keyboard[][] = $cancel_btn;

    return $reply_markup;
  }

  /**
   * Handles /push channel selection.
   *
   * @param string $push_text
   */
  public function handlePushChannel($push_text)
  {
    $message = $this->getUpdate()->message;
    if (is_null($message)) {
      return;
    }

    if (empty($push_text)) {
      $this->replyWithMessage(
        'Текст для рассылки потерялся. Начните заново /push'
      );

      return;
    }

    $channel_name = $this->getUpdate()->message->text;
    if (empty($channel_name)) {
      $this->replyWithMessage(
        'Название канала рассылки пустое. Начните заново /push'
      );

      return;
    } elseif (self::BTN_CANCEL == $channel_name) {
      $this->replyWithMessage(
        'Рассылка отменена.'
      );

      return;
    }

    $d = $this->getBus()
      ->getBot()
      ->getContainer()
      ->get('doctrine');
    $notifications = $d->getRepository('KaulaTelegramBundle:Notification')
      ->findAll();
    $channel_selected = null;
    /** @var \Kaula\TelegramBundle\Entity\Notification $notification_item */
    foreach ($notifications as $notification_item) {
      if ($channel_name == $notification_item->getTitle()) {
        $channel_selected = $notification_item;
        break;
      }
    }
    if (is_null($channel_selected)) {
      $this->replyWithMessage(
        'Некорректное название канала рассылки. Выберите из списка ещё раз.',
        '',
        $this->getReplyKeyboardMarkup_Channels()
      );
      $this->getBus()
        ->getHooker()
        ->createHook(
          $this->getUpdate(),
          get_class($this),
          'handlePushChannel',
          $push_text
        );

      return;
    }

    $this->replyWithMessage(
      sprintf(
        'Подтвердите корректность указанной ниже информации.'.PHP_EOL.PHP_EOL.
        '<b>Канал:</b> %s (%s)'.PHP_EOL.'<b>Длина:</b> %d символов'.PHP_EOL.
        '<b>Текст:</b>',
        $channel_name,
        $channel_selected->getName(),
        mb_strlen($push_text)
      ),
      'HTML'
    );
    $this->replyWithMessage(
      $push_text,
      'HTML'
    );
    $this->replyWithMessage(
      'Если всё правильно, напишите мне ЗАГЛАВНЫМИ БУКВАМИ текст «рассылку подтверждаю». Любой другой текст отменяет рассылку.'
    );

    $this->getBus()
      ->getHooker()
      ->createHook(
        $this->getUpdate(),
        get_class($this),
        'handlePushFinalize',
        serialize(
          [
            'push_text'    => $push_text,
            'notification' => $channel_selected->getName(),
          ]
        )
      );
  }

  /**
   * Handles /push final confirmation and sends push.
   *
   * @param string $push_info
   */
  public function handlePushFinalize($push_info)
  {
    $push_info = unserialize($push_info);
    if (!isset($push_info['push_text']) || empty($push_info['push_text']) ||
      !isset($push_info['notification']) || empty($push_info['notification'])
    ) {
      $this->replyWithMessage(
        'Что-то пошло не так. Начните заново /push'
      );

      return;
    }

    $confirmation_text = $this->getUpdate()->message->text;
    if ("РАССЫЛКУ ПОДТВЕРЖДАЮ" != $confirmation_text) {
      $this->replyWithMessage(
        'Рассылка не подтверждена и отменена.'
      );

      return;
    }

    // Push the message
    $this->getBus()
      ->getBot()
      ->pushNotification(
        $push_info['notification'],
        $push_info['push_text'],
        'HTML'
      );
    $this->getBus()
      ->getBot()
      ->bumpQueue();

    // Report job done
    $this->replyWithMessage(
      sprintf(
        'Рассылка в канал «%s» подтверждена и выслана (%d символов).',
        $push_info['notification'],
        mb_strlen($push_info['push_text'])
      ),
      null,
      new ReplyKeyboardRemove()
    );
  }

}