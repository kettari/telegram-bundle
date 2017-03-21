<?php
/**
 * Created by PhpStorm.
 * User: ant
 * Date: 21.03.2017
 * Time: 17:30
 */

namespace Kaula\TelegramBundle\Telegram\Listener;


use Kaula\TelegramBundle\Telegram\Bot;
use unreal4u\TelegramAPI\Telegram\Types\Update;

interface EventListenerInterface {

  /**
   * Executes event.
   *
   * @param \Kaula\TelegramBundle\Telegram\Bot $bot
   * @param \unreal4u\TelegramAPI\Telegram\Types\Update $update
   * @return void
   */
  public function execute(Bot $bot, Update $update);

}