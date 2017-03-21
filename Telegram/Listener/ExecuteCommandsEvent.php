<?php
/**
 * Created by PhpStorm.
 * User: ant
 * Date: 21.03.2017
 * Time: 17:34
 */

namespace Kaula\TelegramBundle\Telegram\Listener;


use Kaula\TelegramBundle\Telegram\Bot;
use Psr\Log\LoggerInterface;
use unreal4u\TelegramAPI\Telegram\Types\Update;

class ExecuteCommandsEvent implements EventListenerInterface {

  /**
   * Executes event.
   *
   * @param \Kaula\TelegramBundle\Telegram\Bot $bot
   * @param \unreal4u\TelegramAPI\Telegram\Types\Update $update
   * @return void
   */
  public function execute(Bot $bot, Update $update) {
    /** @var LoggerInterface $l */
    $l = $bot->getContainer()
      ->get('logger');

    // Parse command "/start@BotName params"
    if (preg_match('/^\/([a-z_]+)@?([a-z_]*)\s*(.*)$/i', $update->message->text,
      $matches)) {

      if (isset($matches[1]) && ($command_name = $matches[1])) {
        $l->debug('Detected incoming command /{command_name}',
          ['command_name' => $command_name]);

        // Execute command
        $bot->getBus()
          ->executeCommand($command_name, $update);

        return;
      }
    }
    $l->debug('No commands detected within incoming update');
  }


}