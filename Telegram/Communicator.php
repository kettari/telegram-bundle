<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram;


use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use Kettari\TelegramBundle\Telegram\Event\RequestBlockedEvent;
use Kettari\TelegramBundle\Telegram\Event\RequestExceptionEvent;
use Kettari\TelegramBundle\Telegram\Event\RequestSentEvent;
use Kettari\TelegramBundle\Telegram\Event\RequestThrottleEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use unreal4u\TelegramAPI\Abstracts\TelegramMethods;
use unreal4u\TelegramAPI\Telegram\Methods\AnswerCallbackQuery;
use unreal4u\TelegramAPI\Telegram\Methods\EditMessageReplyMarkup;
use unreal4u\TelegramAPI\Telegram\Methods\EditMessageText;
use unreal4u\TelegramAPI\Telegram\Methods\SendChatAction;
use unreal4u\TelegramAPI\Telegram\Methods\SendMessage;
use unreal4u\TelegramAPI\Telegram\Methods\SendPhoto;
use unreal4u\TelegramAPI\Telegram\Types\Custom\InputFile;
use unreal4u\TelegramAPI\Telegram\Types\Message;
use unreal4u\TelegramAPI\TgLog;

class Communicator implements CommunicatorInterface
{
  /**
   * Send message modes
   */
  const PARSE_MODE_PLAIN = '';
  const PARSE_MODE_HTML = 'HTML';
  const PARSE_MODE_MARKDOWN = 'Markdown';

  /**
   * Actions
   */
  const ACTION_TYPING = 'typing';
  const ACTION_UPLOAD_PHOTO = 'upload_photo';
  const ACTION_RECORD_VIDEO = 'record_video';
  const ACTION_UPLOAD_VIDEO = 'upload_video';
  const ACTION_RECORD_AUDIO = 'record_audio';
  const ACTION_UPLOAD_AUDIO = 'upload_audio';
  const ACTION_UPLOAD_DOCUMENT = 'upload_document';
  const ACTION_FIND_LOCATION = 'find_location';

  /**
   * @var \Psr\Log\LoggerInterface
   */
  private $logger;

  /**
   * @var EventDispatcherInterface
   */
  private $dispatcher;

  /**
   * @var ThrottleControl
   */
  private $throttleControl;

  /**
   * @var string
   */
  private $botApiToken;

  /**
   * @var bool
   */
  private $methodDeferred = false;

  /**
   * @var TelegramMethods
   */
  private $deferredMethod;

  /**
   * Communicator constructor.
   *
   * @param \Psr\Log\LoggerInterface $logger
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher
   * @param \Kettari\TelegramBundle\Telegram\ThrottleControl $throttleControl
   * @param string $botApiToken
   */
  public function __construct(
    LoggerInterface $logger,
    EventDispatcherInterface $eventDispatcher,
    ThrottleControl $throttleControl,
    string $botApiToken
  ) {
    $this->logger = $logger;
    $this->dispatcher = $eventDispatcher;
    $this->throttleControl = $throttleControl;
    $this->botApiToken = $botApiToken;

    $this->logger->debug(
      'Communicator instantiated with bot token "{bot_token_api}"',
      ['bot_token_api' => $botApiToken]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function sendMessage(
    int $chatId,
    string $text,
    string $parseMode = self::PARSE_MODE_PLAIN,
    $replyMarkup = null,
    $disableWebPagePreview = false,
    $disableNotification = false,
    $replyToMessageId = null
  ) {
    $this->logger->debug(
      'About to send message to chat ID={chat_id} with parse mode "{parse_mode}"',
      [
        'chat_id'                  => $chatId,
        'parse_mode'               => empty($parseMode) ? 'Plain' : $parseMode,
        'text'                     => $text,
        'reply_markup'             => $replyMarkup,
        'disable_web_page_preview' => $disableWebPagePreview,
        'disable_notification'     => $disableNotification,
        'reply_to_message_id'      => $replyToMessageId,
      ]
    );

    // Build Telegram method
    $sendMessage = new SendMessage();
    $sendMessage->chat_id = $chatId;
    $sendMessage->text = $text;
    $sendMessage->parse_mode = $parseMode;
    $sendMessage->disable_web_page_preview = $disableWebPagePreview;
    $sendMessage->disable_notification = $disableNotification;
    $sendMessage->reply_to_message_id = $replyToMessageId;
    $sendMessage->reply_markup = $replyMarkup;

    /** @var Message $message */
    $message = $this->performRequest($sendMessage);

    $this->logger->debug(
      'Message to chat ID={chat_id} sent',
      ['chat_id' => $chatId,]
    );

    return $message;
  }

  /**
   * Performs actual API request.
   *
   * @param TelegramMethods $method
   * @return \unreal4u\TelegramAPI\Abstracts\TelegramTypes|null
   */
  private function performRequest(TelegramMethods $method)
  {
    /**
     * Check if we could make deferred request: that is return Telegram method
     * in response to Telegram API request.
     */
    if (!$this->methodDeferred) {
      $this->logger->debug(
        'Method "{method_class}" deferred',
        ['method_class' => get_class($method)]
      );

      $this->methodDeferred = true;
      $this->deferredMethod = $method;

      return null;
    } else {
      $this->logger->debug(
        'Pushing deferred method "{method_class}"',
        ['method_class' => get_class($method)]
      );

      // Push deferred method
      $this->performRequest($this->deferredMethod);
    }

    $this->logger->debug(
      'About to perform request for the method "{method_class}"',
      ['method_class' => get_class($method)]
    );

    try {
      // Throttle control to avoid flood
      if ($this->throttleControl->wait()) {

        // Perform request to the Telegram API
        $tgLog = new TgLog(
          $this->botApiToken, $this->logger, $this->getClient()
        );
        $this->throttleControl->requestSent();
        $response = $tgLog->performApiRequest($method);

        // Dispatch event when method is sent
        $requestSentEvent = new RequestSentEvent($method, $response);
        $this->dispatcher->dispatch(RequestSentEvent::NAME, $requestSentEvent);

        return $response;
      } else {
        $this->dispatchThrottleException($method);
      }
    } catch (ClientException $e) {
      switch ($e->getCode()) {
        case HttpResponse::HTTP_FORBIDDEN:
          // User blocked the bot or bot is kicked out of the group
          $this->dispatchBlockedException($method, $e);
          break;
        case HttpResponse::HTTP_TOO_MANY_REQUESTS:
          // Flooded the server with requests :(
          $this->dispatchThrottleException($method);
          break;
        default:
          // Other 4xx HTTP code
          $this->dispatchMethodException($method, $e);
          break;
      }
    } catch (RequestException $e) {
      $this->dispatchMethodException($method, $e);
    }

    $this->logger->debug(
      'Request for the method "{method_class}" performed',
      ['method_class' => get_class($method)]
    );

    return null;
  }

  /**
   * Returns HTTP client.
   *
   * @return \GuzzleHttp\Client
   */
  private function getClient()
  {
    return new Client();
  }

  /**
   * Dispatches throttle exception: that is when we flooded Telegram servers :(
   *
   * @param TelegramMethods $method
   */
  private function dispatchThrottleException(TelegramMethods $method)
  {
    $this->logger->error('Throttle exception');

    // Dispatch event when bot is blocked
    $throttleEvent = new RequestThrottleEvent($method, null);
    $this->dispatcher->dispatch(RequestThrottleEvent::NAME, $throttleEvent);
  }

  /**
   * Dispatches bot is blocked exception: bot is blocked by the user
   * or kicked out of the chat.
   *
   * @param TelegramMethods $method
   * @param ClientException $exception
   */
  private function dispatchBlockedException(
    TelegramMethods $method,
    ClientException $exception
  ) {
    if (method_exists($method, 'chat_id')) {
      /** @noinspection PhpUndefinedFieldInspection */
      $chatId = $method->chat_id;
    } else {
      $chatId = null;
    }

    $this->logger->notice(
      'Bot is blocked by user or kicked out of the chat "{chat_id}"',
      ['chat_id' => $chatId ?? '(undefined)']
    );

    // Dispatch event when bot is blocked
    $blockedEvent = new RequestBlockedEvent(
      $method, $chatId, $exception->getResponse()
    );
    $this->dispatcher->dispatch(RequestBlockedEvent::NAME, $blockedEvent);
  }

  /**
   * General method exception.
   *
   * @param TelegramMethods $method
   * @param RequestException $exception
   */
  private function dispatchMethodException(
    TelegramMethods $method,
    RequestException $exception
  ) {
    $exceptionMessage = $exception->getResponse()
      ->getBody()
      ->getContents();
    $this->logger->error(
      'Request exception: {code} {message}',
      [
        'code'    => $exception->getCode(),
        'message' => $exceptionMessage,
      ]
    );

    // Dispatch event when bot is blocked
    $exceptionEvent = new RequestExceptionEvent(
      $method, $exception->getResponse(), $exceptionMessage
    );
    $this->dispatcher->dispatch(RequestExceptionEvent::NAME, $exceptionEvent);
  }

  /**
   * {@inheritdoc}
   */
  public function sendPhoto(
    int $chatId,
    InputFile $inputFile,
    $caption = null,
    $replyMarkup = null,
    $disableNotification = false,
    $replyToMessageId = null
  ) {
    $this->logger->debug(
      'About to send photo to chat ID={chat_id}',
      [
        'chat_id'              => $chatId,
        'caption'              => $caption,
        'reply_markup'         => $replyMarkup,
        'disable_notification' => $disableNotification,
        'reply_to_message_id'  => $replyToMessageId,
      ]
    );

    // Build Telegram method
    $sendPhoto = new SendPhoto();
    $sendPhoto->chat_id = $chatId;
    $sendPhoto->photo = $inputFile;
    $sendPhoto->caption = $caption;
    $sendPhoto->disable_notification = $disableNotification;
    $sendPhoto->reply_to_message_id = $replyToMessageId;
    $sendPhoto->reply_markup = $replyMarkup;

    /** @var Message $message */
    $message = $this->performRequest($sendPhoto);

    $this->logger->debug(
      'Photo to chat ID={chat_id} sent',
      ['chat_id' => $chatId,]
    );

    return $message;
  }

  /**
   * {@inheritdoc}
   */
  public function editMessageText(
    $chatId = null,
    $messageId = null,
    $inlineMessageId = null,
    string $text,
    $parseMode = null,
    $replyMarkup = null,
    $disableWebPagePreview = false
  ) {
    $this->logger->debug(
      'About to edit message ID={message_id} text in the chat ID={chat_id}',
      [
        'message_id'               => $messageId,
        'chat_id'                  => $chatId,
        'parse_mode'               => empty($parseMode) ? 'Plain' : $parseMode,
        'text'                     => $text,
        'reply_markup'             => $replyMarkup,
        'disable_web_page_preview' => $disableWebPagePreview,
        'inline_message_id'        => $inlineMessageId,
      ]
    );

    // Build Telegram method
    $editMessageText = new EditMessageText();
    $editMessageText->chat_id = $chatId;
    $editMessageText->message_id = $messageId;
    $editMessageText->inline_message_id = $inlineMessageId;
    $editMessageText->text = $text;
    $editMessageText->parse_mode = $parseMode;
    $editMessageText->reply_markup = $replyMarkup;
    $editMessageText->disable_web_page_preview = $disableWebPagePreview;

    /** @var Message $message */
    $message = $this->performRequest($editMessageText);

    $this->logger->debug(
      'Message ID={message_id} text updated in the chat ID={chat_id}',
      ['message_id' => $messageId, 'chat_id' => $chatId,]
    );

    return $message;
  }

  /**
   * {@inheritdoc}
   */
  public function editMessageReplyMarkup(
    $chatId = null,
    $messageId = null,
    $inlineMessageId = null,
    $replyMarkup = null
  ) {
    $this->logger->debug(
      'About to edit message ID={message_id} reply markup in the chat ID={chat_id}',
      [
        'message_id'        => $messageId,
        'chat_id'           => $chatId,
        'reply_markup'      => $replyMarkup,
        'inline_message_id' => $inlineMessageId,
      ]
    );

    // Build Telegram method
    $editMessageMarkup = new EditMessageReplyMarkup();
    $editMessageMarkup->chat_id = $chatId;
    $editMessageMarkup->message_id = $messageId;
    $editMessageMarkup->inline_message_id = $inlineMessageId;
    $editMessageMarkup->reply_markup = $replyMarkup;

    /** @var Message $message */
    $message = $this->performRequest($editMessageMarkup);

    $this->logger->debug(
      'Message ID={message_id} reply markup updated in the chat ID={chat_id}',
      ['message_id' => $messageId, 'chat_id' => $chatId,]
    );

    return $message;
  }

  /**
   * {@inheritdoc}
   */
  public function answerCallbackQuery(
    string $callbackQueryId,
    $text = null,
    $showAlert = false,
    $url = null,
    $cacheTime = 0
  ) {
    $this->logger->debug(
      'About to answer callback query ID={query_id}',
      [
        'query_id'   => $callbackQueryId,
        'text'       => $text,
        'show_alert' => $showAlert,
        'url'        => $url,
        'cache_time' => $cacheTime,
      ]
    );

    // Build Telegram method
    $answerCbQuery = new AnswerCallbackQuery();
    $answerCbQuery->callback_query_id = $callbackQueryId;
    $answerCbQuery->text = $text;
    $answerCbQuery->show_alert = $showAlert;
    $answerCbQuery->url = $url;
    $answerCbQuery->cache_time = $cacheTime;

    /** @var bool $result */
    $result = $this->performRequest($answerCbQuery);

    $this->logger->debug(
      'Callback query ID={query_id} answered',
      ['query_id' => $callbackQueryId,]
    );

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function sendAction(
    int $chatId,
    string $action = self::ACTION_TYPING
  ) {
    $this->logger->debug(
      'About to send "{action_type}" action the chat ID={chat_id}',
      ['chat_id' => $chatId, 'action_type' => $action,]
    );

    // Build Telegram method
    $sendChatAction = new SendChatAction();
    $sendChatAction->chat_id = $chatId;
    $sendChatAction->action = $action;

    /** @var Message $message */
    $this->performRequest($sendChatAction);

    $this->logger->debug(
      'Action "{action_type}" sent the chat ID={chat_id}',
      ['chat_id' => $chatId, 'action_type' => $action,]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function isMethodDeferred(): bool
  {
    return $this->methodDeferred;
  }

  /**
   * {@inheritdoc}
   */
  public function getDeferredMethod()
  {
    return $this->deferredMethod;
  }
}