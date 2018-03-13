<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram\Subscriber;

use Kettari\TelegramBundle\Entity\Audit;
use Kettari\TelegramBundle\Entity\Chat;
use Kettari\TelegramBundle\Entity\User;
use Kettari\TelegramBundle\Telegram\Event\CommandExecutedEvent;
use Kettari\TelegramBundle\Telegram\Event\JoinChatMemberBotEvent;
use Kettari\TelegramBundle\Telegram\Event\JoinChatMemberEvent;
use Kettari\TelegramBundle\Telegram\Event\LeftChatMemberBotEvent;
use Kettari\TelegramBundle\Telegram\Event\LeftChatMemberEvent;
use Kettari\TelegramBundle\Telegram\Event\RequestBlockedEvent;
use Kettari\TelegramBundle\Telegram\Event\RequestExceptionEvent;
use Kettari\TelegramBundle\Telegram\Event\RequestSentEvent;
use Kettari\TelegramBundle\Telegram\Event\TextReceivedEvent;
use Kettari\TelegramBundle\Telegram\Event\UpdateReceivedEvent;
use Kettari\TelegramBundle\Telegram\Event\UserRegisteredEvent;
use Kettari\TelegramBundle\Telegram\MessageTypeResolver;
use Kettari\TelegramBundle\Telegram\UpdateTypeResolver;
use Kettari\TelegramBundle\Telegram\UserHelperTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use unreal4u\TelegramAPI\Telegram\Methods\SendMessage;
use unreal4u\TelegramAPI\Telegram\Types\Update;

class AuditSubscriber extends AbstractBotSubscriber implements EventSubscriberInterface
{
  use UserHelperTrait;

  /**
   * Returns an array of event names this subscriber wants to listen to.
   *
   * The array keys are event names and the value can be:
   *
   *  * The method name to call (priority defaults to 0)
   *  * An array composed of the method name to call and the priority
   *  * An array of arrays composed of the method names to call and respective
   *    priorities, or 0 if unset
   *
   * For instance:
   *
   *  * array('eventName' => 'methodName')
   *  * array('eventName' => array('methodName', $priority))
   *  * array('eventName' => array(array('methodName1', $priority),
   * array('methodName2')))
   *
   * @return array The event names to listen to
   */
  public static function getSubscribedEvents()
  {
    return [
      UpdateReceivedEvent::NAME    => ['onUpdateReceived', 90000],
      CommandExecutedEvent::NAME   => 'onCommandExecuted',
      JoinChatMemberEvent::NAME    => 'onMemberJoined',
      JoinChatMemberBotEvent::NAME => 'onBotJoined',
      LeftChatMemberEvent::NAME    => 'onMemberLeft',
      LeftChatMemberBotEvent::NAME => 'onBotLeft',
      TextReceivedEvent::NAME      => 'onTextReceived',
      RequestBlockedEvent::NAME    => 'onRequestBlocked',
      RequestExceptionEvent::NAME  => 'onRequestException',
      UserRegisteredEvent::NAME    => 'onUserRegistered',
      RequestSentEvent::NAME       => ['onRequestSent', -90000],
    ];
  }

  /**
   * Writes incoming message to the log table in the database.
   *
   * @param UpdateReceivedEvent $event
   */
  public function onUpdateReceived(UpdateReceivedEvent $event)
  {
    // Resolve chat and user objects
    $chatEntity = $this->resolveChat($event->getUpdate());
    $userEntity = $this->resolveUser($event->getUpdate());

    // Format human-readable description
    $updateType = UpdateTypeResolver::getUpdateType($event->getUpdate());
    $description = sprintf('Received update type "%s"', $updateType);
    // Try to make description more informative
    if (!is_null($event->getUpdate()->message)) {
      $messageType = MessageTypeResolver::getMessageType(
        $event->getUpdate()->message
      );
      $messageTypeTitle = MessageTypeResolver::getMessageTypeTitle(
        $messageType
      );
      $description .= sprintf(', message type "%s"', $messageTypeTitle);
    }

    // Write the audit log
    $this->writeAudit(
      UpdateReceivedEvent::NAME,
      $event->getUpdate(),
      $description,
      $chatEntity,
      $userEntity
    );
  }

  /**
   * Tries to track down chat this update was sent from.
   *
   * @param Update $update
   * @return \Kettari\TelegramBundle\Entity\Chat|null
   */
  private function resolveChat(Update $update)
  {
    // Resolve chat object
    $chatId = null;
    if (!is_null($update->message)) {
      $chatId = $update->message->chat->id;
    } elseif (!is_null($update->edited_message)) {
      $chatId = $update->edited_message->chat->id;
    } elseif (!is_null($update->channel_post)) {
      $chatId = $update->channel_post->chat->id;
    } elseif (!is_null($update->callback_query)) {
      if (!is_null($update->callback_query->message)) {
        $chatId = $update->callback_query->message->chat->id;
      }
    }

    return $this->doctrine->getRepository('KettariTelegramBundle:Chat')
      ->findOneByTelegramId($chatId);
  }

  /**
   * Tries to track down user this update was sent from.
   *
   * @param Update $update
   * @return \Kettari\TelegramBundle\Entity\User|null
   */
  private function resolveUser(Update $update)
  {
    // Resolve user object
    $userId = null;
    if (!is_null($update->message) && !is_null($update->message->from)) {
      $userId = $update->message->from->id;
    } elseif (!is_null($update->edited_message) &&
      !is_null($update->edited_message->from)) {
      $userId = $update->edited_message->from->id;
    } elseif (!is_null($update->channel_post) &&
      !is_null($update->channel_post->from)) {
      $userId = $update->channel_post->from->id;
    } elseif (!is_null($update->callback_query)) {
      if (!is_null($update->callback_query->message) &&
        !is_null($update->callback_query->from)) {
        $userId = $update->callback_query->from->id;
      }
    }

    return $this->doctrine->getRepository('KettariTelegramBundle:User')
      ->findOneByTelegramId($userId);
  }

  /**
   * Writes audit.
   *
   * @param string $type
   * @param Update $update
   * @param string $description
   * @param Chat $chatEntity
   * @param User $userEntity
   */
  private function writeAudit(
    string $type,
    $update = null,
    $description = null,
    $chatEntity = null,
    $userEntity = null
  ) {
    $content = null;
    if (!is_null($update)) {
      $content = json_encode(
        get_object_vars($update),
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
      );
    }

    $this->audit($type, $description, $chatEntity, $userEntity, $content);
  }

  /**
   * Audit transaction.
   *
   * @param string $type
   * @param string $description
   * @param Chat $chatEntity
   * @param User $userEntity
   * @param mixed $content
   * @internal param mixed $telegram_data
   */
  private function audit(
    string $type,
    $description = null,
    $chatEntity = null,
    $userEntity = null,
    $content = null
  ) {
    $audit = new Audit();
    $audit->setType($type)
      ->setDescription($description)
      ->setChat($chatEntity)
      ->setUser($userEntity)
      ->setContent($content);

    $this->doctrine->getManager()
      ->persist($audit);
    $this->doctrine->getManager()
      ->flush();
  }

  /**
   * Writes outgoing message to the log table in the database.
   *
   * @param RequestSentEvent $event
   * @throws \ReflectionException
   */
  public function onRequestSent(RequestSentEvent $event)
  {
    $chat_entity = null;
    $method = $event->getMethod();

    // Format human-readable description
    $description = sprintf(
      'Sent request of type "%s"',
      (new \ReflectionClass($method))->getShortName()
    );
    // Try to make description more informative
    if ($method instanceof SendMessage) {
      $chat_entity = $this->doctrine->getRepository(
        'KettariTelegramBundle:Chat'
      )
        ->findOneByTelegramId($method->chat_id);
    }
    /*if (!is_null($event->getUpdate()->message)) {
      $message_type = $this->getBot()
        ->whatMessageType($event->getUpdate()->message);
      $message_type_title = $this->getBot()
        ->getMessageTypeTitle($message_type);
      $description .= sprintf(', message type "%s"', $message_type_title);
    }*/

    // Write the audit log
    $this->audit(
      RequestSentEvent::NAME,
      $description,
      $chat_entity,
      null,
      print_r(
        $event->getMethod()
          ->export(),
        true
      )
    );
  }

  /**
   * Writes audit log when command is executed.
   *
   * @param \Kettari\TelegramBundle\Telegram\Event\CommandExecutedEvent $event
   */
  public function onCommandExecuted(CommandExecutedEvent $event)
  {
    // Resolve chat and user objects
    $chatEntity = $this->resolveChat($event->getUpdate());
    $userEntity = $this->resolveUser($event->getUpdate());

    // Format human-readable description
    $description = sprintf(
      'Executed command "%s" → "%s"',
      $event->getCommandName(),
      $this->formatChatTitle($chatEntity)
    );

    // Write the audit log
    $this->writeAudit(
      CommandExecutedEvent::NAME,
      $event->getUpdate(),
      $description,
      $chatEntity,
      $userEntity
    );
  }

  /**
   * Formats chat title.
   *
   * @param Chat|null $chatEntity
   * @return string
   */
  private function formatChatTitle($chatEntity)
  {
    if (!is_null($chatEntity)) {
      $chatName = trim($chatEntity->getTitle());
      if (empty($chatName)) {
        $chatName = trim(
          $chatEntity->getFirstName().' '.$chatEntity->getLastName()
        );
      }
      $chatName .= ' ('.$chatEntity->getId().')';
    } else {
      $chatName = '(chat unknown)';
    }

    return $chatName;
  }

  /**
   * Writes audit log when chat member joined.
   *
   * @param \Kettari\TelegramBundle\Telegram\Event\JoinChatMemberEvent $event
   */
  public function onMemberJoined(JoinChatMemberEvent $event)
  {
    // Resolve chat and user objects
    $chatEntity = $this->resolveChat($event->getUpdate());
    /** @var User $userEntity */
    $userEntity = $this->doctrine->getRepository('KettariTelegramBundle:User')
      ->findOneByTelegramId($event->getJoinedUser()->id);

    // Format human-readable description
    $description = sprintf(
      'User "%s" joined the chat "%s"',
      self::formatUserName($userEntity),
      $this->formatChatTitle($chatEntity)
    );

    // Write the audit log
    $this->writeAudit(
      JoinChatMemberEvent::NAME,
      $event->getUpdate(),
      $description,
      $chatEntity,
      $userEntity
    );
    // Write log
    $this->logger->info(
      'User "{user_name}" joined the chat "{chat_name}"',
      [
        'user_name' => self::formatUserName($userEntity),
        'chat_name' => $this->formatChatTitle($chatEntity),
      ]
    );
  }

  /**
   * Writes audit log when current bot joined the group.
   *
   * @param \Kettari\TelegramBundle\Telegram\Event\JoinChatMemberBotEvent $event
   */
  public function onBotJoined(JoinChatMemberBotEvent $event)
  {
    // Resolve chat and user objects
    $chatEntity = $event->getChatEntity();
    $userEntity = $event->getUserEntity();

    // Format message parts
    $user_name = $userEntity->getUsername();
    $external_name = $userEntity->getFirstName();

    // Format human-readable description
    $description = sprintf(
      'Bot "%s" ("%s") joined the chat "%s"',
      $user_name,
      $external_name,
      $this->formatChatTitle($chatEntity)
    );

    // Write the audit log
    $this->writeAudit(
      JoinChatMemberBotEvent::NAME,
      $event->getUpdate(),
      $description,
      $chatEntity,
      $userEntity
    );
    // Write log
    $this->logger->info(
      'Bot "{user_name}" ("{external_name}") joined the chat "{chat_name}"',
      [
        'user_name'     => $user_name,
        'external_name' => $external_name,
        'chat_name'     => $this->formatChatTitle($chatEntity),
      ]
    );
  }

  /**
   * Writes audit log when chat member left.
   *
   * @param \Kettari\TelegramBundle\Telegram\Event\LeftChatMemberEvent $event
   */
  public function onMemberLeft(LeftChatMemberEvent $event)
  {
    // Resolve chat and user objects
    $chatEntity = $this->resolveChat($event->getUpdate());
    /** @var User $userEntity */
    $userEntity = $this->doctrine->getRepository('KettariTelegramBundle:User')
      ->findOneByTelegramId($event->getMessage()->left_chat_member->id);

    // Format human-readable description
    $description = sprintf(
      'User "%s" left the chat "%s"',
      self::formatUserName($userEntity),
      $this->formatChatTitle($chatEntity)
    );

    // Write the audit log
    $this->writeAudit(
      LeftChatMemberEvent::NAME,
      $event->getUpdate(),
      $description,
      $chatEntity,
      $userEntity
    );
    // Write log
    $this->logger->info(
      'User "{user_name}" left the chat "{chat_name}"',
      [
        'user_name' => self::formatUserName($userEntity),
        'chat_name' => $this->formatChatTitle($chatEntity),
      ]
    );
  }

  /**
   * Writes audit log when bot left the group.
   *
   * @param \Kettari\TelegramBundle\Telegram\Event\LeftChatMemberBotEvent $event
   */
  public function onBotLeft(LeftChatMemberBotEvent $event)
  {
    // Resolve chat and user objects
    $chatEntity = $event->getChatEntity();
    $userEntity = $event->getUserEntity();

    // Format message parts
    $userName = $userEntity->getUsername();
    $externalName = $userEntity->getFirstName();

    // Format human-readable description
    $description = sprintf(
      'Bot "%s" ("%s") left the chat "%s"',
      $userName,
      $externalName,
      $this->formatChatTitle($chatEntity)
    );

    // Write the audit log
    $this->writeAudit(
      LeftChatMemberBotEvent::NAME,
      $event->getUpdate(),
      $description,
      $chatEntity,
      $userEntity
    );
    // Write log
    $this->logger->info(
      'Bot "{user_name}" ("{external_name}") left the chat "{chat_name}"',
      [
        'user_name'     => $userName,
        'external_name' => $externalName,
        'chat_name'     => $this->formatChatTitle($chatEntity),
      ]
    );
  }

  /**
   * Writes audit log when text is received.
   *
   * @param TextReceivedEvent $event
   */
  public function onTextReceived(TextReceivedEvent $event)
  {
    // Parse command "/start@BotName params". We need NOT a command
    if (!preg_match(
      '/^\/([a-z_]+)@?([a-z_]*)\s*(.*)$/i',
      $event->getText()
    )) {
      // Resolve chat and user objects
      $chatEntity = $this->resolveChat($event->getUpdate());
      $userEntity = $this->resolveUser($event->getUpdate());

      // Format human-readable description
      $description = sprintf(
        '"%s" → "%s": %s',
        self::formatUserName($userEntity),
        $this->formatChatTitle($chatEntity),
        $event->getText()
      );

      // Write the audit log
      $this->writeAudit(
        TextReceivedEvent::NAME,
        $event->getUpdate(),
        $description,
        $chatEntity,
        $userEntity
      );
    }
  }

  /**
   * Writes audit log when request is blocked.
   *
   * @param RequestBlockedEvent $event
   */
  public function onRequestBlocked(RequestBlockedEvent $event)
  {
    // Resolve chat object
    /** @var Chat $chatEntity */
    $chatEntity = $this->doctrine->getRepository('KettariTelegramBundle:Chat')
      ->findOneByTelegramId($event->getChatId());

    // Format human-readable description
    $description = sprintf(
      'Bot is blocked in the chat "%s"',
      $this->formatChatTitle($chatEntity)
    );

    // Write the audit log
    $this->writeAudit(
      RequestBlockedEvent::NAME,
      null,
      $description,
      $chatEntity
    );
  }

  /**
   * Writes audit log when user is registered.
   *
   * @param \Kettari\TelegramBundle\Telegram\Event\UserRegisteredEvent $event
   */
  public function onUserRegistered(UserRegisteredEvent $event)
  {
    // Resolve chat and user objects
    $chat_entity = $this->resolveChat($event->getUpdate());
    $user_entity = $event->getRegisteredUser();

    // Format human-readable description
    $description = sprintf(
      'User "%s" registered',
      self::formatUserName($user_entity)
    );

    // Write the audit log
    $this->writeAudit(
      UserRegisteredEvent::NAME,
      $event->getUpdate(),
      $description,
      $chat_entity,
      $user_entity
    );
  }

  /**
   * Writes audit log when exception occurred during request.
   *
   * @param RequestExceptionEvent $event
   */
  public function onRequestException(RequestExceptionEvent $event)
  {
    // Format human-readable description
    $description = sprintf(
      'Request exception "%s" with status code %d',
      $event->getResponse()
        ->getReasonPhrase(),
      $event->getResponse()
        ->getStatusCode()
    );

    // Write the audit log
    $this->audit(
      RequestExceptionEvent::NAME,
      $description,
      null,
      null,
      $event->getExceptionMessage()
    );
  }

}