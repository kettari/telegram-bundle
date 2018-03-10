<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram\Subscriber;

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
use Kettari\TelegramBundle\Telegram\UserHq;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use unreal4u\TelegramAPI\Telegram\Methods\SendMessage;
use unreal4u\TelegramAPI\Telegram\Types\Update;

class AuditSubscriber extends AbstractBotSubscriber implements EventSubscriberInterface
{
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
    $chat_entity = $this->resolveChat($event->getUpdate());
    $user_entity = $this->resolveUser($event->getUpdate());

    // Format human-readable description
    $update_type = $this->getBot()
      ->whatUpdateType($event->getUpdate());
    $description = sprintf('Received update type "%s"', $update_type);
    // Try to make description more informative
    if (!is_null($event->getUpdate()->message)) {
      $message_type = $this->getBot()
        ->whatMessageType($event->getUpdate()->message);
      $message_type_title = $this->getBot()
        ->getMessageTypeTitle($message_type);
      $description .= sprintf(', message type "%s"', $message_type_title);
    }

    // Write the audit log
    $this->writeAudit(
      UpdateReceivedEvent::NAME,
      $event->getUpdate(),
      $description,
      $chat_entity,
      $user_entity
    );
  }

  /**
   * Writes audit.
   *
   * @param string $type
   * @param Update $update
   * @param string $description
   * @param Chat $chat_entity
   * @param User $user_entity
   */
  private function writeAudit(
    $type,
    $update = null,
    $description = null,
    $chat_entity = null,
    $user_entity = null
  ) {
    $content = null;
    if (!is_null($update)) {
      $content = json_encode(
        get_object_vars($update),
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
      );
    }

    $this->getBot()
      ->audit($type, $description, $chat_entity, $user_entity, $content);
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
      $chat_entity = $this->getDoctrine()
        ->getRepository('KettariTelegramBundle:Chat')
        ->findOneBy(['telegram_id' => $method->chat_id]);
    }
    /*if (!is_null($event->getUpdate()->message)) {
      $message_type = $this->getBot()
        ->whatMessageType($event->getUpdate()->message);
      $message_type_title = $this->getBot()
        ->getMessageTypeTitle($message_type);
      $description .= sprintf(', message type "%s"', $message_type_title);
    }*/

    // Write the audit log
    $this->getBot()
      ->audit(
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
    $chat_entity = $this->resolveChat($event->getUpdate());
    $user_entity = $this->resolveUser($event->getUpdate());

    // Format human-readable description
    $description = sprintf(
      'Executed command "%s" → "%s"',
      $event->getCommand(),
      $this->formatChatTitle($chat_entity)
    );

    // Write the audit log
    $this->writeAudit(
      CommandExecutedEvent::NAME,
      $event->getUpdate(),
      $description,
      $chat_entity,
      $user_entity
    );
  }

  /**
   * Formats chat title.
   *
   * @param Chat $chat_entity
   * @return string
   */
  private function formatChatTitle($chat_entity)
  {
    if (!is_null($chat_entity)) {
      $chat_name = trim($chat_entity->getTitle());
      if (empty($chat_name)) {
        $chat_name = trim(
          $chat_entity->getFirstName().' '.$chat_entity->getLastName()
        );
      }
      $chat_name .= ' ('.$chat_entity->getId().')';
    } else {
      $chat_name = '(chat unknown)';
    }

    return $chat_name;
  }

  /**
   * Writes audit log when chat member joined.
   *
   * @param \Kettari\TelegramBundle\Telegram\Event\JoinChatMemberEvent $event
   */
  public function onMemberJoined(JoinChatMemberEvent $event)
  {
    // Resolve chat and user objects
    $chat_entity = $this->resolveChat($event->getUpdate());
    $user_entity = $this->getDoctrine()
      ->getRepository('KettariTelegramBundle:User')
      ->findOneBy(['telegram_id' => $event->getJoinedUser()->id]);

    // Format human-readable description
    $description = sprintf(
      'User "%s" joined the chat "%s"',
      UserHq::formatUserName($user_entity, true),
      $this->formatChatTitle($chat_entity)
    );

    // Write the audit log
    $this->writeAudit(
      JoinChatMemberEvent::NAME,
      $event->getUpdate(),
      $description,
      $chat_entity,
      $user_entity
    );
    // Write log
    $this->getBot()
      ->getLogger()
      ->info(
        'User "{user_name}" joined the chat "{chat_name}"',
        [
          'user_name' => UserHq::formatUserName($user_entity, true),
          'chat_name' => $this->formatChatTitle($chat_entity),
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
    $chat_entity = $event->getChatEntity();
    $user_entity = $event->getUserEntity();

    // Format message parts
    $user_name = $user_entity->getUsername();
    $external_name = $user_entity->getFirstName();

    // Format human-readable description
    $description = sprintf(
      'Bot "%s" ("%s") joined the chat "%s"',
      $user_name,
      $external_name,
      $this->formatChatTitle($chat_entity)
    );

    // Write the audit log
    $this->writeAudit(
      JoinChatMemberBotEvent::NAME,
      $event->getUpdate(),
      $description,
      $chat_entity,
      $user_entity
    );
    // Write log
    $this->getBot()
      ->getLogger()
      ->info(
        'Bot "{user_name}" ("{external_name}") joined the chat "{chat_name}"',
        [
          'user_name'     => $user_name,
          'external_name' => $external_name,
          'chat_name'     => $this->formatChatTitle($chat_entity),
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
    $chat_entity = $this->resolveChat($event->getUpdate());
    $user_entity = $this->getDoctrine()
      ->getRepository('KettariTelegramBundle:User')
      ->findOneBy(
        ['telegram_id' => $event->getMessage()->left_chat_member->id]
      );

    // Format human-readable description
    $description = sprintf(
      'User "%s" left the chat "%s"',
      UserHq::formatUserName($user_entity, true),
      $this->formatChatTitle($chat_entity)
    );

    // Write the audit log
    $this->writeAudit(
      LeftChatMemberEvent::NAME,
      $event->getUpdate(),
      $description,
      $chat_entity,
      $user_entity
    );
    // Write log
    $this->getBot()
      ->getLogger()
      ->info(
        'User "{user_name}" left the chat "{chat_name}"',
        [
          'user_name' => UserHq::formatUserName($user_entity, true),
          'chat_name' => $this->formatChatTitle($chat_entity),
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
    $chat_entity = $event->getChatEntity();
    $user_entity = $event->getUserEntity();

    // Format message parts
    $user_name = $user_entity->getUsername();
    $external_name = $user_entity->getFirstName();

    // Format human-readable description
    $description = sprintf(
      'Bot "%s" ("%s") left the chat "%s"',
      $user_name,
      $external_name,
      $this->formatChatTitle($chat_entity)
    );

    // Write the audit log
    $this->writeAudit(
      LeftChatMemberBotEvent::NAME,
      $event->getUpdate(),
      $description,
      $chat_entity,
      $user_entity
    );
    // Write log
    $this->getBot()
      ->getLogger()
      ->info(
        'Bot "{user_name}" ("{external_name}") left the chat "{chat_name}"',
        [
          'user_name'     => $user_name,
          'external_name' => $external_name,
          'chat_name'     => $this->formatChatTitle($chat_entity),
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
    )
    ) {
      // Resolve chat and user objects
      $chat_entity = $this->resolveChat($event->getUpdate());
      $user_entity = $this->resolveUser($event->getUpdate());

      // Format human-readable description
      $description = sprintf(
        '"%s" → "%s": %s',
        UserHq::formatUserName($user_entity, true),
        $this->formatChatTitle($chat_entity),
        $event->getText()
      );

      // Write the audit log
      $this->writeAudit(
        TextReceivedEvent::NAME,
        $event->getUpdate(),
        $description,
        $chat_entity,
        $user_entity
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
    $chat_entity = $this->getDoctrine()
      ->getRepository('KettariTelegramBundle:Chat')
      ->findOneBy(
        ['telegram_id' => $event->getChatId()]
      );

    // Format human-readable description
    $description = sprintf(
      'Bot is blocked in the chat "%s"',
      $this->formatChatTitle($chat_entity)
    );

    // Write the audit log
    $this->writeAudit(
      RequestBlockedEvent::NAME,
      null,
      $description,
      $chat_entity
    );
  }

  /**
   * Writes audit log when user is registered.
   *
   * @param \Kettari\TelegramBundle\Telegram\Event\UserRegisteredEvent $e
   */
  public function onUserRegistered(UserRegisteredEvent $e)
  {
    // Resolve chat and user objects
    $chat_entity = $this->resolveChat($e->getUpdate());
    $user_entity = $e->getRegisteredUser();

    // Format human-readable description
    $description = sprintf(
      'User "%s" registered',
      UserHq::formatUserName($user_entity, true)
    );

    // Write the audit log
    $this->writeAudit(
      UserRegisteredEvent::NAME,
      $e->getUpdate(),
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
    $this->getBot()
      ->audit(
        RequestExceptionEvent::NAME,
        $description,
        null,
        null,
        $event->getExceptionMessage()
      );
  }

}