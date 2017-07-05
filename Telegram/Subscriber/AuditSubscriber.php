<?php
/**
 * Created by PhpStorm.
 * User: ant
 * Date: 25.04.2017
 * Time: 14:19
 */

namespace Kaula\TelegramBundle\Telegram\Subscriber;

use Kaula\TelegramBundle\Telegram\Event\CommandExecutedEvent;
use Kaula\TelegramBundle\Telegram\Event\CommandReceivedEvent;
use Kaula\TelegramBundle\Telegram\Event\JoinChatMemberBotEvent;
use Kaula\TelegramBundle\Telegram\Event\JoinChatMemberEvent;
use Kaula\TelegramBundle\Telegram\Event\LeftChatMemberBotEvent;
use Kaula\TelegramBundle\Telegram\Event\LeftChatMemberEvent;
use Kaula\TelegramBundle\Telegram\Event\RequestBlockedEvent;
use Kaula\TelegramBundle\Telegram\Event\RequestExceptionEvent;
use Kaula\TelegramBundle\Telegram\Event\RequestSentEvent;
use Kaula\TelegramBundle\Telegram\Event\TextReceivedEvent;
use Kaula\TelegramBundle\Telegram\Event\UpdateReceivedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
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
    $this->logInput($event->getUpdate());
  }

  /**
   * Logs input message to the database.
   *
   * @param \unreal4u\TelegramAPI\Telegram\Types\Update $update
   */
  private function logInput(Update $update)
  {
    $type = UpdateReceivedEvent::NAME;
    $update_type = $this->getBot()
      ->whatUpdateType($update);
    $description = sprintf('Received update type "%s"', $update_type);
    $content = json_encode(
      get_object_vars($update),
      JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
    );

    // Try to make description more informative
    if (!is_null($update->message)) {
      $message_type = $this->getBot()
        ->whatMessageType($update->message);
      $message_type_title = $this->getBot()
        ->getMessageTypeTitle($message_type);
      $description .= sprintf(', message type "%s"', $message_type_title);
    }
    // Resolve chat and user objects
    $chat = $this->resolveChat($update);
    $user = $this->resolveUser($update);
    $this->getBot()
      ->audit($type, $description, $chat, $user, $content);
  }

  /**
   * Writes outgoing message to the log table in the database.
   *
   * @param RequestSentEvent $event
   */
  public function onRequestSent(RequestSentEvent $event)
  {
    // TODO Audit output (sent) messages
    //$this->logOutput($event);
  }

  /**
   * Writes audit log when command is executed.
   *
   * @param \Kaula\TelegramBundle\Telegram\Event\CommandExecutedEvent $event
   */
  public function onCommandExecuted(CommandExecutedEvent $event)
  {
    // Audit
    $type = CommandReceivedEvent::NAME;
    $content = json_encode(
      get_object_vars($event->getUpdate()),
      JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
    );
    // Resolve chat and user objects
    $chat = $this->resolveChat($event->getUpdate());
    $user = $this->resolveUser($event->getUpdate());

    if (!is_null($chat)) {
      $chat_name = trim($chat->getTitle());
      if (empty($chat_name)) {
        $chat_name = trim($chat->getFirstName().' '.$chat->getLastName());
      }
      $chat_id = $chat->getId();
    } else {
      $chat_name = '(chat unknown)';
      $chat_id = '(chat id unknown)';
    }

    $description = sprintf(
      'Executed command "%s" → "%s" (%s)',
      $event->getCommand(),
      $chat_name,
      $chat_id
    );

    $this->getBot()
      ->audit($type, $description, $chat, $user, $content);
  }

  /**
   * Writes audit log when chat member joined.
   *
   * @param \Kaula\TelegramBundle\Telegram\Event\JoinChatMemberEvent $event
   */
  public function onMemberJoined(JoinChatMemberEvent $event)
  {
    // Audit
    $type = JoinChatMemberEvent::NAME;
    $content = json_encode(
      get_object_vars($event->getUpdate()),
      JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
    );
    // Resolve chat and user objects
    $chat_entity = $this->resolveChat($event->getUpdate());
    // Resolve user
    $user_entity = $this->getDoctrine()
      ->getRepository('KaulaTelegramBundle:User')
      ->findOneBy(['telegram_id' => $event->getJoinedUser()->id]);

    // Format message parts
    $user_name = trim(
      $user_entity->getFirstName().' '.$user_entity->getLastName()
    );
    $external_name = trim(
      $user_entity->getExternalFirstName().' '.
      $user_entity->getExternalLastName()
    );
    if (empty($external_name)) {
      $external_name = 'no external name';
    }
    if (!is_null($chat_entity)) {
      $chat_name = trim($chat_entity->getTitle());
      if (empty($chat_name)) {
        $chat_name = trim(
          $chat_entity->getFirstName().' '.$chat_entity->getLastName()
        );
      }
      $chat_id = $chat_entity->getId();
    } else {
      $chat_name = '(chat unknown)';
      $chat_id = 'chat id unknown';
    }
    $description = sprintf(
      'User "%s" ("%s") joined the chat "%s" (%s)',
      $user_name,
      $external_name,
      $chat_name,
      $chat_id
    );
    // Add audit
    $this->getBot()
      ->audit($type, $description, $chat_entity, $user_entity, $content);
    // Write log
    $this->getBot()
      ->getLogger()
      ->info(
        'User "{telegram_user_name}" ("{external_name}") joined the chat "{chat_name}" ({chat_id})',
        [
          'telegram_user_name' => $user_name,
          'external_name'      => $external_name,
          'chat_name'          => $chat_name,
          'chat_id'            => $chat_id,
        ]
      );
  }

  /**
   * Writes audit log when current bot joined the group.
   *
   * @param \Kaula\TelegramBundle\Telegram\Event\JoinChatMemberBotEvent $event
   */
  public function onBotJoined(JoinChatMemberBotEvent $event)
  {
    // Audit
    $type = JoinChatMemberBotEvent::NAME;
    $content = json_encode(
      get_object_vars($event->getUpdate()),
      JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
    );
    // Resolve chat and user objects
    $chat_entity = $event->getChatEntity();
    $user_entity = $event->getUserEntity();

    // Format message parts
    $user_name = $user_entity->getUsername();
    $external_name = $user_entity->getFirstName();
    if (!is_null($chat_entity)) {
      $chat_name = trim($chat_entity->getTitle());
      if (empty($chat_name)) {
        $chat_name = trim(
          $chat_entity->getFirstName().' '.$chat_entity->getLastName()
        );
      }
      $chat_id = $chat_entity->getId();
    } else {
      $chat_name = '(chat unknown)';
      $chat_id = '(chat id unknown)';
    }
    $description = sprintf(
      'Bot "%s" ("%s") joined the chat "%s" (%s)',
      $user_name,
      $external_name,
      $chat_name,
      $chat_id
    );
    // Add audit
    $this->getBot()
      ->audit($type, $description, $chat_entity, $user_entity, $content);
    // Write log
    $this->getBot()
      ->getLogger()
      ->info(
        'Bot "{telegram_user_name}" ("{external_name}") joined the chat "{chat_name}" ({chat_id})',
        [
          'telegram_user_name' => $user_name,
          'external_name'      => $external_name,
          'chat_name'          => $chat_name,
          'chat_id'            => $chat_id,
        ]
      );
  }

  /**
   * Writes audit log when chat member left.
   *
   * @param \Kaula\TelegramBundle\Telegram\Event\LeftChatMemberEvent $event
   */
  public function onMemberLeft(LeftChatMemberEvent $event)
  {
    // Audit
    $type = LeftChatMemberEvent::NAME;
    $content = json_encode(
      get_object_vars($event->getUpdate()),
      JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
    );
    // Resolve chat and user objects
    $chat_entity = $this->resolveChat($event->getUpdate());
    // Resolve user
    $user_entity = $this->getDoctrine()
      ->getRepository('KaulaTelegramBundle:User')
      ->findOneBy(
        ['telegram_id' => $event->getMessage()->left_chat_member->id]
      );

    // Format message parts
    $user_name = trim(
      $user_entity->getFirstName().' '.$user_entity->getLastName()
    );
    $external_name = trim(
      $user_entity->getExternalFirstName().' '.
      $user_entity->getExternalLastName()
    );
    if (empty($external_name)) {
      $external_name = 'no external name';
    }
    if (!is_null($chat_entity)) {
      $chat_name = trim($chat_entity->getTitle());
      if (empty($chat_name)) {
        $chat_name = trim(
          $chat_entity->getFirstName().' '.$chat_entity->getLastName()
        );
      }
      $chat_id = $chat_entity->getId();
    } else {
      $chat_name = '(chat unknown)';
      $chat_id = 'chat id unknown';
    }
    $description = sprintf(
      'User "%s" ("%s") left the chat "%s" (%s)',
      $user_name,
      $external_name,
      $chat_name,
      $chat_id
    );
    // Add audit
    $this->getBot()
      ->audit($type, $description, $chat_entity, $user_entity, $content);
    // Write log
    $this->getBot()
      ->getLogger()
      ->info(
        'User "{telegram_user_name}" ("{external_name}") left the chat "{chat_name}" ({chat_id})',
        [
          'telegram_user_name' => $user_name,
          'external_name'      => $external_name,
          'chat_name'          => $chat_name,
          'chat_id'            => $chat_entity->getId(),
        ]
      );
  }

  /**
   * Writes audit log when bot left the group.
   *
   * @param \Kaula\TelegramBundle\Telegram\Event\LeftChatMemberBotEvent $event
   */
  public function onBotLeft(LeftChatMemberBotEvent $event)
  {
    // Audit
    $type = LeftChatMemberBotEvent::NAME;
    $content = json_encode(
      get_object_vars($event->getUpdate()),
      JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
    );
    // Resolve chat and user objects
    $chat_entity = $event->getChatEntity();
    $user_entity = $event->getUserEntity();

    // Format message parts
    $user_name = $user_entity->getUsername();
    $external_name = $user_entity->getFirstName();
    if (!is_null($chat_entity)) {
      $chat_name = trim($chat_entity->getTitle());
      if (empty($chat_name)) {
        $chat_name = trim(
          $chat_entity->getFirstName().' '.$chat_entity->getLastName()
        );
      }
      $chat_id = $chat_entity->getId();
    } else {
      $chat_name = '(chat unknown)';
      $chat_id = '(chat id unknown)';
    }
    $description = sprintf(
      'Bot "%s" ("%s") left the chat "%s" (%s)',
      $user_name,
      $external_name,
      $chat_name,
      $chat_id
    );
    // Add audit
    $this->getBot()
      ->audit($type, $description, $chat_entity, $user_entity, $content);
    // Write log
    $this->getBot()
      ->getLogger()
      ->info(
        'Bot "{telegram_user_name}" ("{external_name}") left the chat "{chat_name}" ({chat_id})',
        [
          'telegram_user_name' => $user_name,
          'external_name'      => $external_name,
          'chat_name'          => $chat_name,
          'chat_id'            => $chat_entity->getId(),
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
    // Parse command "/start@BotName params"
    if (!preg_match(
      '/^\/([a-z_]+)@?([a-z_]*)\s*(.*)$/i',
      $event->getText()
    )
    ) {

      // Not a command

      // Audit
      $type = TextReceivedEvent::NAME;
      $content = json_encode(
        get_object_vars($event->getUpdate()),
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
      );
      // Resolve chat and user objects
      $chat_entity = $this->resolveChat($event->getUpdate());
      $user_entity = $this->resolveUser($event->getUpdate());

      // Format message parts
      $user_name = trim(
        $user_entity->getFirstName().' '.$user_entity->getLastName()
      );
      $external_name = trim(
        $user_entity->getExternalFirstName().' '.
        $user_entity->getExternalLastName()
      );
      if (empty($external_name)) {
        $external_name = 'no external name';
      }
      if (!is_null($chat_entity)) {
        $chat_name = trim($chat_entity->getTitle());
        if (empty($chat_name)) {
          $chat_name = trim(
            $chat_entity->getFirstName().' '.$chat_entity->getLastName()
          );
        }
        $chat_id = $chat_entity->getId();
      } else {
        $chat_name = '(chat unknown)';
        $chat_id = 'chat id unknown';
      }
      $description = sprintf(
        '"%s" ("%s") → "%s" (%s): %s',
        $user_name,
        $external_name,
        $chat_name,
        $chat_id,
        $event->getText()
      );
      // Add audit
      $this->getBot()
        ->audit($type, $description, $chat_entity, $user_entity, $content);
    }
  }

  /**
   * Writes audit log when request is blocked.
   *
   * @param RequestBlockedEvent $event
   */
  public function onRequestBlocked(RequestBlockedEvent $event)
  {
    // Audit
    $type = RequestBlockedEvent::NAME;
    // Resolve chat and user objects
    $chat = $this->getDoctrine()
      ->getRepository('KaulaTelegramBundle:Chat')
      ->findOneBy(
        ['telegram_id' => $event->getChatId()]
      );

    if (!is_null($chat)) {
      $chat_name = trim($chat->getTitle());
      if (empty($chat_name)) {
        $chat_name = trim($chat->getFirstName().' '.$chat->getLastName());
      }
      $chat_id = $chat->getId();
    } else {
      $chat_name = '(chat unknown)';
      $chat_id = '(chat id unknown)';
    }
    $description = sprintf(
      'Bot is blocked in the chat "%s" (%s)',
      $chat_name,
      $chat_id
    );
    // Add audit
    $this->getBot()
      ->audit($type, $description, $chat);
  }

  /**
   * Writes audit log when exception occurred during request.
   *
   * @param RequestExceptionEvent $event
   */
  public function onRequestException(RequestExceptionEvent $event)
  {
    // Audit
    $type = RequestExceptionEvent::NAME;
    $description = sprintf(
      'Request exception "%s" with status code %d',
      $event->getResponse()
        ->getReasonPhrase(),
      $event->getResponse()
        ->getStatusCode()
    );
    $content = $event->getMethod()
      ->export();

    // Add audit
    $this->getBot()
      ->audit($type, $description, null, null, print_r($content, true));
  }

}