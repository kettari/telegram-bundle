<?php
/**
 * Created by PhpStorm.
 * User: ant
 * Date: 16.03.2017
 * Time: 17:46
 */

namespace Kaula\TelegramBundle\Telegram;


use Doctrine\Bundle\DoctrineBundle\Registry;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use Kaula\TelegramBundle\Entity\Audit;
use Kaula\TelegramBundle\Entity\Chat;
use Kaula\TelegramBundle\Entity\Queue;
use Kaula\TelegramBundle\Entity\User;
use Kaula\TelegramBundle\Exception\TelegramBundleException;
use Kaula\TelegramBundle\Telegram\Command\HelpCommand;
use Kaula\TelegramBundle\Telegram\Command\ListRolesCommand;
use Kaula\TelegramBundle\Telegram\Command\PushCommand;
use Kaula\TelegramBundle\Telegram\Command\SettingsCommand;
use Kaula\TelegramBundle\Telegram\Command\StartCommand;
use Kaula\TelegramBundle\Telegram\Command\UserManCommand;
use Kaula\TelegramBundle\Telegram\Event\MessageReceivedEvent;
use Kaula\TelegramBundle\Telegram\Event\RequestBlockedEvent;
use Kaula\TelegramBundle\Telegram\Event\RequestExceptionEvent;
use Kaula\TelegramBundle\Telegram\Event\RequestSentEvent;
use Kaula\TelegramBundle\Telegram\Event\RequestThrottleEvent;
use Kaula\TelegramBundle\Telegram\Event\TerminateEvent;
use Kaula\TelegramBundle\Telegram\Event\UpdateIncomingEvent;
use Kaula\TelegramBundle\Telegram\Event\UpdateReceivedEvent;
use Kaula\TelegramBundle\Telegram\Subscriber\AuditSubscriber;
use Kaula\TelegramBundle\Telegram\Subscriber\ChatMemberSubscriber;
use Kaula\TelegramBundle\Telegram\Subscriber\CommandSubscriber;
use Kaula\TelegramBundle\Telegram\Subscriber\CurrentUserSubscriber;
use Kaula\TelegramBundle\Telegram\Subscriber\FilterSubscriber;
use Kaula\TelegramBundle\Telegram\Subscriber\GroupSubscriber;
use Kaula\TelegramBundle\Telegram\Subscriber\HookerSubscriber;
use Kaula\TelegramBundle\Telegram\Subscriber\IdentityWatchdogSubscriber;
use Kaula\TelegramBundle\Telegram\Subscriber\MessageSubscriber;
use Kaula\TelegramBundle\Telegram\Subscriber\MigrationSubscriber;
use Kaula\TelegramBundle\Telegram\Subscriber\TextSubscriber;
use Kaula\TelegramBundle\Telegram\Subscriber\UserRegistrationSubscriber;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Symfony\Component\Stopwatch\Stopwatch;
use unreal4u\TelegramAPI\Abstracts\KeyboardMethods;
use unreal4u\TelegramAPI\Abstracts\TelegramMethods;
use unreal4u\TelegramAPI\Telegram\Methods\AnswerCallbackQuery;
use unreal4u\TelegramAPI\Telegram\Methods\EditMessageReplyMarkup;
use unreal4u\TelegramAPI\Telegram\Methods\EditMessageText;
use unreal4u\TelegramAPI\Telegram\Methods\SendChatAction;
use unreal4u\TelegramAPI\Telegram\Methods\SendMessage;
use unreal4u\TelegramAPI\Telegram\Methods\SendPhoto;
use unreal4u\TelegramAPI\Telegram\Types\Custom\InputFile;
use unreal4u\TelegramAPI\Telegram\Types\Message;
use unreal4u\TelegramAPI\Telegram\Types\Update;
use unreal4u\TelegramAPI\TgLog;

class Bot
{

  // Update types
  const UT_MESSAGE = 'message';
  const UT_EDITED_MESSAGE = 'edited_message';
  const UT_CHANNEL_POST = 'channel_post';
  const UT_EDITED_CHANNEL_POST = 'edited_channel_post';
  const UT_INLINE_QUERY = 'inline_query';
  const UT_CHOSEN_INLINE_RESULT = 'chosen_inline_result';
  const UT_CALLBACK_QUERY = 'callback_query';

  // Event types
  const MT_NOTHING = 0;
  const MT_TEXT = 1;
  const MT_AUDIO = 2;
  const MT_DOCUMENT = 4;
  const MT_GAME = 8;
  const MT_PHOTO = 16;
  const MT_STICKER = 32;
  const MT_VIDEO = 64;
  const MT_VOICE = 128;
  const MT_CONTACT = 256;
  const MT_LOCATION = 512;
  const MT_VENUE = 1024;
  const MT_NEW_CHAT_MEMBER = 2048;
  const MT_LEFT_CHAT_MEMBER = 4096;
  const MT_NEW_CHAT_TITLE = 8192;
  const MT_NEW_CHAT_PHOTO = 16384;
  const MT_DELETE_CHAT_PHOTO = 32768;
  const MT_GROUP_CHAT_CREATED = 65536;
  const MT_SUPERGROUP_CHAT_CREATED = 131072;
  const MT_CHANNEL_CHAT_CREATED = 262144;
  const MT_MIGRATE_TO_CHAT_ID = 524288;
  const MT_MIGRATE_FROM_CHAT_ID = 1048576;
  const MT_PINNED_MESSAGE = 2097152;
  const MT_NEW_CHAT_MEMBERS_MANY = 4194304;
  const MT_SUCCESSFUL_PAYMENT = 8388608;
  const MT_INVOICE = 16777216;
  const MT_VIDEO_NOTE = 33554432;
  const MT_ANY = 67108863;

  // Captions of the event types
  /**
   * @var ThrottleSingleton
   */
  protected $throttle_singleton;

  /**
   * Symfony container.
   *
   * @var ContainerInterface
   */
  protected $container;

  /**
   * @var \Kaula\TelegramBundle\Telegram\CommandBus
   */
  protected $bus;

  /**
   * Message type titles.
   *
   * @var array
   */
  private $mt_captions = [
    self::MT_TEXT                    => 'MT_TEXT',
    self::MT_AUDIO                   => 'MT_AUDIO',
    self::MT_DOCUMENT                => 'MT_DOCUMENT',
    self::MT_GAME                    => 'MT_GAME',
    self::MT_PHOTO                   => 'MT_PHOTO',
    self::MT_STICKER                 => 'MT_STICKER',
    self::MT_VIDEO                   => 'MT_VIDEO',
    self::MT_VOICE                   => 'MT_VOICE',
    self::MT_CONTACT                 => 'MT_CONTACT',
    self::MT_LOCATION                => 'MT_LOCATION',
    self::MT_VENUE                   => 'MT_VENUE',
    self::MT_NEW_CHAT_MEMBER         => 'MT_NEW_CHAT_MEMBER',
    self::MT_LEFT_CHAT_MEMBER        => 'MT_LEFT_CHAT_MEMBER',
    self::MT_NEW_CHAT_TITLE          => 'MT_NEW_CHAT_TITLE',
    self::MT_NEW_CHAT_PHOTO          => 'MT_NEW_CHAT_PHOTO',
    self::MT_DELETE_CHAT_PHOTO       => 'MT_DELETE_CHAT_PHOTO',
    self::MT_GROUP_CHAT_CREATED      => 'MT_GROUP_CHAT_CREATED',
    self::MT_SUPERGROUP_CHAT_CREATED => 'MT_SUPERGROUP_CHAT_CREATED',
    self::MT_CHANNEL_CHAT_CREATED    => 'MT_CHANNEL_CHAT_CREATED',
    self::MT_MIGRATE_TO_CHAT_ID      => 'MT_MIGRATE_TO_CHAT_ID',
    self::MT_MIGRATE_FROM_CHAT_ID    => 'MT_MIGRATE_FROM_CHAT_ID',
    self::MT_PINNED_MESSAGE          => 'MT_PINNED_MESSAGE',
    self::MT_NEW_CHAT_MEMBERS_MANY   => 'MT_NEW_CHAT_MEMBERS_MANY',
    self::MT_SUCCESSFUL_PAYMENT      => 'MT_SUCCESSFUL_PAYMENT',
    self::MT_INVOICE                 => 'MT_INVOICE',
    self::MT_VIDEO_NOTE              => 'MT_VIDEO_NOTE',
  ];

  /**
   * User manager.
   *
   * @var UserHq
   */
  private $user_hq;

  /**
   * @var EventDispatcherInterface
   */
  private $event_dispatcher;

  /**
   * @var bool
   */
  private $request_handled = false;

  /**
   * Bot constructor.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * @internal param string $token
   */
  public function __construct(ContainerInterface $container)
  {
    $this->container = $container;
    $this->bus = new CommandBus($this);
    $this->user_hq = new UserHq($this);

    // Assign event dispatcher here. We may override this by setting different
    // dispatcher with setEventDispatcher()
    $this->event_dispatcher = $this->getContainer()
      ->get('event_dispatcher');

    // Throttle control
    $this->throttle_singleton = ThrottleSingleton::getInstance();

  }

  /**
   * Returns container.
   *
   * @return \Symfony\Component\DependencyInjection\ContainerInterface
   */
  public function getContainer(): ContainerInterface
  {
    return $this->container;
  }

  /**
   * Adds default subscribers to the dispatcher.
   *
   * @return Bot
   */
  public function addDefaultSubscribers()
  {
    // telegram.update.incoming
    $this->getEventDispatcher()
      ->addSubscriber(new FilterSubscriber());

    // telegram.update.received
    $this->getEventDispatcher()
      ->addSubscriber(new AuditSubscriber($this));
    $this->getEventDispatcher()
      ->addSubscriber(new CurrentUserSubscriber($this));
    $this->getEventDispatcher()
      ->addSubscriber(new HookerSubscriber($this));

    // telegram.message.received
    $this->getEventDispatcher()
      ->addSubscriber(new IdentityWatchdogSubscriber($this));
    $this->getEventDispatcher()
      ->addSubscriber(new MessageSubscriber($this));

    // telegram.text.received
    $this->getEventDispatcher()
      ->addSubscriber(new TextSubscriber($this));

    // telegram.command.received
    $this->getEventDispatcher()
      ->addSubscriber(new CommandSubscriber($this));

    // telegram.chatmember.*
    $this->getEventDispatcher()
      ->addSubscriber(new ChatMemberSubscriber($this));

    // telegram.group.created
    $this->getEventDispatcher()
      ->addSubscriber(new GroupSubscriber($this));

    // telegram.chat.*
    $this->getEventDispatcher()
      ->addSubscriber(new MigrationSubscriber($this));

    // User registered, command /register
    $this->getEventDispatcher()
      ->addSubscriber(new UserRegistrationSubscriber($this));

    return $this;
  }

  /**
   * @return EventDispatcherInterface
   */
  public function getEventDispatcher(): EventDispatcherInterface
  {
    return $this->event_dispatcher;
  }

  /**
   * @param EventDispatcherInterface $event_dispatcher
   * @return Bot
   */
  public function setEventDispatcher(EventDispatcherInterface $event_dispatcher
  ): Bot {
    $this->event_dispatcher = $event_dispatcher;

    return $this;
  }

  /**
   * Adds default commands to the CommandBus.
   *
   * @return Bot
   */
  public function addDefaultCommands()
  {
    $this->getBus()
      ->registerCommand(StartCommand::class)
      ->registerCommand(SettingsCommand::class)
      ->registerCommand(HelpCommand::class)
      ->registerCommand(ListRolesCommand::class)
      ->registerCommand(PushCommand::class)
      ->registerCommand(UserManCommand::class);

    return $this;
  }

  /**
   * @return \Kaula\TelegramBundle\Telegram\CommandBus
   */
  public function getBus(): CommandBus
  {
    return $this->getContainer()
      ->get('kettari_telegram.bus');
  }

  /**
   * @return Registry
   */
  public function getDoctrine(): Registry
  {
    return $this->getContainer()
      ->get('doctrine');
  }

  /**
   * Returns title of the message type.
   *
   * @param int $message_type
   * @return string
   */
  public function getMessageTypeTitle($message_type)
  {
    $types = [];
    foreach ($this->mt_captions as $key => $caption) {
      if ($key & $message_type) {
        $types[] = $caption;
      }
    }

    return (count($types) > 0) ? implode(', ', $types) : 'MT_UNKNOWN';
  }

  /**
   * Handles update.
   *
   * @return void
   * @throws RuntimeException
   */
  public function handleUpdate()
  {
    /** @var LoggerInterface $l */
    $l = $this->container->get('logger');
    $dispatcher = $this->getEventDispatcher();

    // Dispatch event when we need to filter incoming data
    $update_incoming_event = new UpdateIncomingEvent();
    $dispatcher->dispatch(UpdateIncomingEvent::NAME, $update_incoming_event);
    if (is_null($update = $update_incoming_event->getUpdate())) {
      throw new RuntimeException(
        'Update object is null after "telegram.update.incoming" event'
      );
    }

    // Get update type
    $update_type = $this->whatUpdateType($update);
    $l->info(
      'Handling update of type "{type}"',
      ['type' => $update_type, 'update' => $update]
    );

    // Dispatch event when we got the Update object
    $update_received_event = new UpdateReceivedEvent($update);
    $dispatcher->dispatch(UpdateReceivedEvent::NAME, $update_received_event);

    // Check if current user is blocked and abort execution if true
    if ($this->getUserHq()
      ->isUserBlocked()) {
      return;
    }

    // Check type of the update and dispatch more specific events
    switch ($update_type) {
      case self::UT_MESSAGE:
        $message_received_event = new MessageReceivedEvent($update);
        $dispatcher->dispatch(
          MessageReceivedEvent::NAME,
          $message_received_event
        );
        break;
    }

    // Dispatch termination
    $terminate_event = new TerminateEvent($update);
    $dispatcher->dispatch(TerminateEvent::NAME, $terminate_event);
  }

  /**
   * Returns type of the update.
   *
   * @param \unreal4u\TelegramAPI\Telegram\Types\Update $update
   * @return string
   * @throws TelegramBundleException
   */
  public function whatUpdateType(Update $update)
  {
    if (!is_null($update->message)) {
      return self::UT_MESSAGE;
    }
    if (!is_null($update->edited_message)) {
      return self::UT_EDITED_MESSAGE;
    }
    if (!is_null($update->channel_post)) {
      return self::UT_CHANNEL_POST;
    }
    if (!is_null($update->edited_channel_post)) {
      return self::UT_EDITED_CHANNEL_POST;
    }
    if (!is_null($update->inline_query)) {
      return self::UT_INLINE_QUERY;
    }
    if (!is_null($update->chosen_inline_result)) {
      return self::UT_CHOSEN_INLINE_RESULT;
    }
    if (!is_null($update->callback_query)) {
      return self::UT_CALLBACK_QUERY;
    }

    throw new TelegramBundleException('Unknown update type.');
  }

  /**
   * @return UserHq
   */
  public function getUserHq(): UserHq
  {
    return $this->user_hq;
  }

  /**
   * Use this method to edit only the reply markup of messages sent by the bot
   * or via the bot (for inline bots). On success, if edited message is sent by
   * the bot, the edited Message is returned, otherwise True is returned.
   *
   * @param integer|string $chat_id Required if inline_message_id is not
   *   specified. Unique identifier for the target chat or username of the
   *   target channel (in the format @channelusername)
   * @param integer $message_id Required if inline_message_id is not specified.
   *   Identifier of the sent message
   * @param string $inline_message_id Required if chat_id and message_id are
   *   not specified. Identifier of the inline message
   * @param string $text Text of the message to be sent
   * @param string $parse_mode Send Markdown or HTML, if you want Telegram apps
   *   to show bold, italic, fixed-width text or inline URLs in your bot's
   *   message.
   * @param KeyboardMethods $reply_markup Additional interface options. A
   *   JSON-serialized object for an inline keyboard, custom reply keyboard,
   *   instructions to remove reply keyboard or to force a reply from the user.
   * @param bool $disable_web_page_preview Disables link previews for links in
   *   this message
   * @return Message
   */
  public function editMessageText(
    $chat_id = null,
    $message_id = null,
    $inline_message_id = null,
    $text,
    $parse_mode = null,
    $reply_markup = null,
    $disable_web_page_preview = false
  ) {
    /** @var LoggerInterface $l */
    $l = $this->container->get('logger');

    $edit_message_text = new EditMessageText();
    $edit_message_text->chat_id = $chat_id;
    $edit_message_text->message_id = $message_id;
    $edit_message_text->inline_message_id = $inline_message_id;
    $edit_message_text->text = $text;
    $edit_message_text->parse_mode = $parse_mode;
    $edit_message_text->reply_markup = $reply_markup;
    $edit_message_text->disable_web_page_preview = $disable_web_page_preview;

    // Allow some debug info
    $l->debug(
      'Bot is editing message text',
      ['message' => print_r($edit_message_text, true)]
    );

    /** @var Message $message */
    $message = $this->performRequest($edit_message_text);

    return $message;
  }

  /**
   * Performs actual API request.
   *
   * @param \unreal4u\TelegramAPI\Abstracts\TelegramMethods $method
   * @return \unreal4u\TelegramAPI\Abstracts\TelegramTypes
   */
  private function performRequest(TelegramMethods $method)
  {
    /** @var LoggerInterface $l */
    $l = $this->getContainer()
      ->get('logger');
    // Get configuration
    $config = $this->getContainer()
      ->getParameter('kettari_telegram');
    $dispatcher = $this->getEventDispatcher();
    $client = new Client();

    try {
      // Throttle control to avoid flood
      if ($this->getThrottleSingleton()
        ->wait()) {
        $tg_log = new TgLog($config['api_token'], $l, $client);
        $this->getThrottleSingleton()
          ->requestSent();

        // Perform request to the Telegram API
        $response = $tg_log->performApiRequest($method);

        // Dispatch event when method is sent
        $request_sent_event = new RequestSentEvent($method, $response);
        $dispatcher->dispatch(RequestSentEvent::NAME, $request_sent_event);

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

    return null;
  }

  /**
   * @return ThrottleSingleton
   */
  public function getThrottleSingleton(): ThrottleSingleton
  {
    return $this->throttle_singleton;
  }

  /**
   * @param mixed $method
   */
  private function dispatchThrottleException($method)
  {
    $l = $this->getContainer()
      ->get('logger');
    $dispatcher = $this->getEventDispatcher();

    $l->error('Throttle exception');

    // Dispatch event when bot is blocked
    $throttle_event = new RequestThrottleEvent($method, null);
    $dispatcher->dispatch(RequestThrottleEvent::NAME, $throttle_event);
  }

  /**
   * @param mixed $method
   * @param ClientException $exception
   */
  private function dispatchBlockedException($method, ClientException $exception)
  {
    $l = $this->getContainer()
      ->get('logger');
    $dispatcher = $this->getEventDispatcher();

    if (method_exists($method, 'chat_id')) {
      /** @noinspection PhpUndefinedFieldInspection */
      $chat_id = $method->chat_id;
    } else {
      $chat_id = null;
    }

    $l->notice(
      'Bot is blocked or kicked out of the chat "{chat_id}"',
      ['chat_id' => $chat_id ?? '(undefined)']
    );

    // Dispatch event when bot is blocked
    $blocked_event = new RequestBlockedEvent(
      $chat_id, $method, $exception->getResponse()
    );
    $dispatcher->dispatch(RequestBlockedEvent::NAME, $blocked_event);
  }

  /**
   * @param TelegramMethods $method
   * @param RequestException $exception
   */
  private function dispatchMethodException($method, RequestException $exception)
  {
    $l = $this->getContainer()
      ->get('logger');
    $dispatcher = $this->getEventDispatcher();

    $exception_message = $exception->getResponse()
      ->getBody()
      ->getContents();
    $l->error(
      'Request exception: {code} {message}',
      [
        'code'    => $exception->getCode(),
        'message' => $exception_message,
      ]
    );

    // Dispatch event when bot is blocked
    $exception_event = new RequestExceptionEvent(
      $method, $exception->getResponse(), $exception_message
    );
    $dispatcher->dispatch(RequestExceptionEvent::NAME, $exception_event);
  }

  /**
   * Use this method to edit only the reply markup of messages sent by the bot
   * or via the bot (for inline bots). On success, if edited message is sent by
   * the bot, the edited Message is returned, otherwise True is returned.
   *
   * @param integer|string $chat_id Required if inline_message_id is not
   *   specified. Unique identifier for the target chat or username of the
   *   target channel (in the format @channelusername)
   * @param integer $message_id Required if inline_message_id is not specified.
   *   Identifier of the sent message
   * @param string $inline_message_id Required if chat_id and message_id are
   *   not specified. Identifier of the inline message
   * @param \unreal4u\TelegramAPI\Telegram\Types\Inline\Keyboard\Markup $reply_markup A
   *   JSON-serialized object for an inline keyboard.
   * @return Message
   */
  public function editMessageReplyMarkup(
    $chat_id = null,
    $message_id = null,
    $inline_message_id = null,
    $reply_markup = null
  ) {
    /** @var LoggerInterface $l */
    $l = $this->container->get('logger');

    $edit_message_markup = new EditMessageReplyMarkup();
    $edit_message_markup->chat_id = $chat_id;
    $edit_message_markup->message_id = $message_id;
    $edit_message_markup->inline_message_id = $inline_message_id;
    $edit_message_markup->reply_markup = $reply_markup;

    // Allow some debug info
    $l->debug(
      'Bot is editing reply markup keyboard',
      ['message' => print_r($edit_message_markup, true)]
    );

    /** @var Message $message */
    $message = $this->performRequest($edit_message_markup);

    return $message;
  }

  /**
   * Use this method to send answers to callback queries sent from inline
   * keyboards. The answer will be displayed to the user as a notification at
   * the top of the chat screen or as an alert. On success, True is returned.
   *
   * @param string $callback_query_id Unique identifier for the query to be
   *   answered
   * @param string $text Text of the notification. If not specified, nothing
   *   will be shown to the user, 0-200 characters
   * @param bool $show_alert If true, an alert will be shown by the client
   *   instead of a notification at the top of the chat screen. Defaults to
   *   false.
   * @param string $url URL that will be opened by the user's client. If you
   *   have created a Game and accepted the conditions via @Botfather, specify
   *   the URL that opens your game – note that this will only work if the
   *   query comes from a callback_game button. Otherwise, you may use links
   *   like telegram.me/your_bot?start=XXXX that open your bot with a
   *   parameter.
   * @param int $cache_time The maximum amount of time in seconds that the
   *   result of the callback query may be cached client-side. Telegram apps
   *   will support caching starting in version 3.14. Defaults to 0.
   * @return bool
   */
  public function answerCallbackQuery(
    $callback_query_id,
    $text = null,
    $show_alert = false,
    $url = null,
    $cache_time = 0
  ) {
    /** @var LoggerInterface $l */
    $l = $this->container->get('logger');

    $answer_cb_query = new AnswerCallbackQuery();
    $answer_cb_query->callback_query_id = $callback_query_id;
    $answer_cb_query->text = $text;
    $answer_cb_query->show_alert = $show_alert;
    $answer_cb_query->url = $url;
    $answer_cb_query->cache_time = $cache_time;

    // Allow some debug info
    $l->debug(
      'Bot is answering callback query',
      ['message' => print_r($answer_cb_query, true)]
    );

    /** @var bool $result */
    $result = $this->performRequest($answer_cb_query);

    return $result;
  }

  /**
   * Use this method when you need to tell the user that something is happening
   * on the bot's side. The status is set for 5 seconds or less (when a message
   * arrives from your bot, Telegram clients clear its typing status).
   *
   * Example: The ImageBot needs some time to process a request and upload the
   * image. Instead of sending a text message along the lines of “Retrieving
   * image, please wait…”, the bot may use sendChatAction with action =
   * upload_photo. The user will see a “sending photo” status for the bot. We
   * only recommend using this method when a response from the bot will take a
   * noticeable amount of time to arrive.
   *
   * Objects defined as-is july 2016
   *
   * @see https://core.telegram.org/bots/api#sendchataction
   * @param string $chat_id Unique identifier for the target chat or username
   *   of the target channel (in the format @channelusername)
   * @param string $action Type of action to broadcast. Choose one, depending
   *   on what the user is about to receive: typing for text messages,
   *   upload_photo for photos, record_video or upload_video for videos,
   *   record_audio or upload_audio for audio files, upload_document for
   *   general files, find_location for location data.
   */
  public function sendAction($chat_id, $action = 'typing')
  {
    $send_chat_action = new SendChatAction();
    $send_chat_action->chat_id = $chat_id;
    $send_chat_action->action = $action;

    /** @var Message $message */
    $this->performRequest($send_chat_action);
  }

  /**
   * Push notification to users.
   *
   * @param string $notification Notification name. If $recipient is specified,
   *   this option is ignored.
   * @param string $text Text of the message to be sent
   * @param string $parseMode Send Markdown or HTML, if you want Telegram apps
   *   to show bold, italic, fixed-width text or inline URLs in your bot's
   *   message.
   * @param KeyboardMethods $replyMarkup Additional interface options. A
   *   JSON-serialized object for an inline keyboard, custom reply keyboard,
   *   instructions to remove reply keyboard or to force a reply from the user.
   * @param bool $disableWebPagePreview Disables link previews for links in
   *   this message
   * @param bool $disableNotification Sends the message silently. iOS users
   *   will not receive a notification, Android users will receive a
   *   notification with no sound.
   * @param \Kaula\TelegramBundle\Entity\User|null $recipient If specified,
   *   send notification only to this user.
   * @param string|null $chatId Only if $recipient is specified: use this chat
   *   instead of private. If skipped, message is send privately
   */
  public function pushNotification(
    $notification,
    $text,
    $parseMode = '',
    $replyMarkup = null,
    $disableWebPagePreview = false,
    $disableNotification = false,
    User $recipient = null,
    $chatId = null
  ) {
    $now = new \DateTime('now', new \DateTimeZone('UTC'));
    $d = $this->getContainer()
      ->get('doctrine');
    /** @var LoggerInterface $l */
    $l = $this->getContainer()
      ->get('logger');

    $l->info(
      'Pushing notification "{notification}"',
      [
        'notification'     => $notification,
        'telegram_user_id' => !is_null($recipient) ? $recipient->getTelegramId(
        ) : '(all)',
        'chat_id'          => $chatId,
      ]
    );

    $subscribers = $this->getEligibleSubscribers($notification, $recipient);
    $l->info(
      'Subscribers to receive notification: {subscribers_count}',
      ['subscribers_count' => count($subscribers)]
    );

    /** @var \Kaula\TelegramBundle\Entity\User $userItem */
    foreach ($subscribers as $userItem) {
      if ($userItem->isBlocked()) {
        continue;
      }

      if (is_null(
        $chat = $d->getRepository('KaulaTelegramBundle:Chat')
          ->findOneBy(
            [
              'telegram_id' => is_null($chatId) ? $userItem->getTelegramId(
              ) : $chatId,
            ]
          )
      )) {
        $l->error(
          sprintf(
            'Queue for push failed: unable to find chat for given user (telegram_user_id=%s)',
            $userItem->getTelegramId()
          )
        );

        return;
      }

      $queue = new Queue();
      $queue->setStatus('pending')
        ->setCreated($now)
        ->setChat($chat)
        ->setText($text)
        ->setParseMode($parseMode)
        ->setReplyMarkup(
          !is_null($replyMarkup) ? serialize($replyMarkup) : null
        )
        ->setDisableWebPagePreview($disableWebPagePreview)
        ->setDisableNotification($disableNotification);
      $d->getManager()
        ->persist($queue);
    }
    $d->getManager()
      ->flush();
  }

  /**
   * Returns array of subscribers who will receive notification.
   *
   * @param string $notification Name of the notification
   * @param User $recipient Recipient who intended to receive notification
   * @return array|\Doctrine\Common\Collections\Collection
   */
  private function getEligibleSubscribers($notification, $recipient)
  {
    if (!is_null($recipient)) {
      return [$recipient];
    }

    // Load notification and users
    /** @var \Kaula\TelegramBundle\Entity\Notification $doctrine_notification */
    $doctrine_notification = $this->getContainer()
      ->get('doctrine')
      ->getRepository(
        'KaulaTelegramBundle:Notification'
      )
      ->findOneBy(['name' => $notification]);
    if (is_null($doctrine_notification)) {
      return [];
    }

    return $doctrine_notification->getUsers();
  }

  /**
   * Sends part of queued messages.
   *
   * @param int $bump_size Count of items to send in this bump operation
   */
  public function bumpQueue($bump_size = 10)
  {
    $stopwatch = new Stopwatch();
    $stopwatch->start('bumpQueue');
    /** @var LoggerInterface $l */
    $l = $this->getContainer()
      ->get('logger');
    $items_count = 0;

    $queue = $this->getContainer()
      ->get('doctrine')
      ->getRepository(
        'KaulaTelegramBundle:Queue'
      )
      ->findBy(['status' => 'pending'], ['created' => 'ASC'], $bump_size);

    /** @var Queue $queue_item */
    foreach ($queue as $queue_item) {
      try {

        $this->sendMessage(
          $queue_item->getChat()
            ->getTelegramId(),
          $queue_item->getText(),
          $queue_item->getParseMode(),
          is_null(
            $queue_item->getReplyMarkup()
          ) ? null : unserialize($queue_item->getReplyMarkup()),
          $queue_item->getDisableWebPagePreview(),
          $queue_item->getDisableNotification()
        );
        $queue_item->setStatus('sent');

      } catch (\Exception $e) {

        $queue_item->setStatus('error')
          ->setExceptionMessage($e->getMessage());
        $l->warning(
          'Exception while sending queued message #{id}: {exception_message}',
          [
            'id'                => $queue_item->getId(),
            'exception_message' => $e->getMessage(),
          ]
        );

      } finally {

        $items_count++;
        $queue_item->setUpdated(new \DateTime('now', new \DateTimeZone('UTC')));

      }
    }
    $this->getContainer()
      ->get('doctrine')
      ->getManager()
      ->flush();

    $bump_event = $stopwatch->stop('bumpQueue');
    $elapsed = ($bump_event->getDuration() / 1000);
    $l->info(
      'Bumped queue: processed {items_count} items, elapsed {time_elapsed} seconds. Average items per second: {items_per_second}',
      [
        'items_count'      => $items_count,
        'time_elapsed'     => sprintf('%.2f', $elapsed),
        'items_per_second' => sprintf(
          '%.2f',
          ($elapsed > 0) ? ($items_count / $elapsed) : 0
        ),
      ]
    );
  }

  /**
   * Use this method to send text messages. On success, the sent Message is
   * returned.
   *
   * @param string $chat_id Unique identifier for the target chat or username
   *   of the target channel (in the format @channelusername)
   * @param string $text Text of the message to be sent
   * @param string $parse_mode Send Markdown or HTML, if you want Telegram apps
   *   to show bold, italic, fixed-width text or inline URLs in your bot's
   *   message.
   * @param KeyboardMethods $reply_markup Additional interface options. A
   *   JSON-serialized object for an inline keyboard, custom reply keyboard,
   *   instructions to remove reply keyboard or to force a reply from the user.
   * @param bool $disable_web_page_preview Disables link previews for links in
   *   this message
   * @param bool $disable_notification Sends the message silently. iOS users
   *   will not receive a notification, Android users will receive a
   *   notification with no sound.
   * @param string $reply_to_message_id If the message is a reply, ID of the
   *   original message
   *
   * @return Message
   */
  public function sendMessage(
    $chat_id,
    $text,
    $parse_mode = null,
    $reply_markup = null,
    $disable_web_page_preview = false,
    $disable_notification = false,
    $reply_to_message_id = null
  ) {
    /** @var LoggerInterface $l */
    $l = $this->container->get('logger');

    $send_message = new SendMessage();
    $send_message->chat_id = $chat_id;
    $send_message->text = $text;
    $send_message->parse_mode = $parse_mode ?? '';
    $send_message->disable_web_page_preview = $disable_web_page_preview;
    $send_message->disable_notification = $disable_notification;
    $send_message->reply_to_message_id = $reply_to_message_id;
    $send_message->reply_markup = $reply_markup;

    // Allow some debug info
    $l->info(
      'Bot is sending message',
      ['message' => print_r($send_message, true)]
    );

    /** @var Message $message */
    $message = $this->performRequest($send_message);

    return $message;
  }

  /**
   * Use this method to send photos. On success, the sent Message is
   * returned.
   *
   * @param string $chatId Unique identifier for the target chat or username
   *   of the target channel (in the format @channelusername)
   * @param string|\unreal4u\TelegramAPI\Telegram\Types\Custom\InputFile $inputFile Photo
   *   to send. Pass a file_id as String to send a photo that exists on the
   *   Telegram servers (recommended), pass an HTTP URL as a String for
   *   Telegram to get a photo from the Internet, or upload a new photo using
   *   the InputFile class.
   * @param string $caption Photo caption (may also be used when resending
   *   photos by file_id), 0-200 characters
   * @param KeyboardMethods $replyMarkup Additional interface options. A
   *   JSON-serialized object for an inline keyboard, custom reply keyboard,
   *   instructions to remove reply keyboard or to force a reply from the user.
   * @param bool $disableNotification Sends the message silently. iOS users
   *   will not receive a notification, Android users will receive a
   *   notification with no sound.
   * @param string $replyToMessageId If the message is a reply, ID of the
   *   original message
   *
   * @return Message
   */
  public function sendPhoto(
    $chatId,
    InputFile $inputFile,
    $caption = null,
    $replyMarkup = null,
    $disableNotification = false,
    $replyToMessageId = null
  ) {
    /** @var LoggerInterface $l */
    $l = $this->container->get('logger');

    $sendPhoto = new SendPhoto();
    $sendPhoto->chat_id = $chatId;
    $sendPhoto->photo = $inputFile;
    $sendPhoto->caption = $caption;
    $sendPhoto->disable_notification = $disableNotification;
    $sendPhoto->reply_to_message_id = $replyToMessageId;
    $sendPhoto->reply_markup = $replyMarkup;

    // Allow some debug info
    $l->info('Bot is sending photo');

    /** @var Message $message */
    $message = $this->performRequest($sendPhoto);

    return $message;
  }

  /**
   * @return bool
   */
  public function isRequestHandled(): bool
  {
    return $this->request_handled;
  }

  /**
   * @param bool $request_handled
   *
   * @return Bot
   */
  public function setRequestHandled(bool $request_handled)
  {
    $this->request_handled = $request_handled;

    return $this;
  }

  /**
   * Returns type of the message.
   *
   * @param Message $message
   * @return integer
   */
  public function whatMessageType(Message $message)
  {
    $message_type = self::MT_NOTHING;
    if (!empty($message->text)) {
      $message_type = $message_type | self::MT_TEXT;
    }
    if (!empty($message->audio)) {
      $message_type = $message_type | self::MT_AUDIO;
    }
    if (!empty($message->document)) {
      $message_type = $message_type | self::MT_DOCUMENT;
    }
    if (!empty($message->game)) {
      $message_type = $message_type | self::MT_GAME;
    }
    if (!empty($message->photo)) {
      $message_type = $message_type | self::MT_PHOTO;
    }
    if (!empty($message->sticker)) {
      $message_type = $message_type | self::MT_STICKER;
    }
    if (!empty($message->video)) {
      $message_type = $message_type | self::MT_VIDEO;
    }
    if (!empty($message->voice)) {
      $message_type = $message_type | self::MT_VOICE;
    }
    if (!empty($message->contact)) {
      $message_type = $message_type | self::MT_CONTACT;
    }
    if (!empty($message->location)) {
      $message_type = $message_type | self::MT_LOCATION;
    }
    if (!empty($message->venue)) {
      $message_type = $message_type | self::MT_VENUE;
    }
    if (!empty($message->new_chat_member)) {
      $message_type = $message_type | self::MT_NEW_CHAT_MEMBER;
    }
    if (is_array($message->new_chat_members) &&
      (count($message->new_chat_members) > 0)) {
      $message_type = $message_type | self::MT_NEW_CHAT_MEMBERS_MANY;
    }
    if (!empty($message->left_chat_member)) {
      $message_type = $message_type | self::MT_LEFT_CHAT_MEMBER;
    }
    if (!empty($message->new_chat_title)) {
      $message_type = $message_type | self::MT_NEW_CHAT_TITLE;
    }
    if (!empty($message->new_chat_photo)) {
      $message_type = $message_type | self::MT_NEW_CHAT_PHOTO;
    }
    if ($message->delete_chat_photo) {
      $message_type = $message_type | self::MT_DELETE_CHAT_PHOTO;
    }
    if ($message->group_chat_created) {
      $message_type = $message_type | self::MT_GROUP_CHAT_CREATED;
    }
    if ($message->supergroup_chat_created) {
      $message_type = $message_type | self::MT_SUPERGROUP_CHAT_CREATED;
    }
    if ($message->channel_chat_created) {
      $message_type = $message_type | self::MT_CHANNEL_CHAT_CREATED;
    }
    if (0 != $message->migrate_to_chat_id) {
      $message_type = $message_type | self::MT_MIGRATE_TO_CHAT_ID;
    }
    if (0 != $message->migrate_from_chat_id) {
      $message_type = $message_type | self::MT_MIGRATE_FROM_CHAT_ID;
    }
    if (!empty($message->pinned_message)) {
      $message_type = $message_type | self::MT_PINNED_MESSAGE;
    }
    if (!empty($message->successful_payment)) {
      $message_type = $message_type | self::MT_SUCCESSFUL_PAYMENT;
    }
    if (!empty($message->invoice)) {
      $message_type = $message_type | self::MT_INVOICE;
    }
    if (!empty($message->video_note)) {
      $message_type = $message_type | self::MT_VIDEO_NOTE;
    }

    return $message_type;
  }

  /**
   * Returns logger object.
   *
   * @return LoggerInterface
   */
  public function getLogger()
  {
    return $this->getContainer()
      ->get('logger');
  }

  /**
   * Audit transaction.
   *
   * @param string $type
   * @param string $description
   * @param Chat $chat_entity
   * @param User $user_entity
   * @param mixed $content
   * @internal param mixed $telegram_data
   */
  public function audit(
    $type,
    $description = null,
    $chat_entity = null,
    $user_entity = null,
    $content = null
  ) {
    $audit = new Audit();
    $audit->setType($type)
      ->setDescription($description)
      ->setChat($chat_entity)
      ->setUser($user_entity)
      ->setContent($content);

    $em = $this->getContainer()
      ->get('doctrine')
      ->getManager();
    $em->persist($audit);
    $em->flush();
  }

}