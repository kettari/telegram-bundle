<?php
/**
 * Created by PhpStorm.
 * User: ant
 * Date: 16.03.2017
 * Time: 18:18
 */

namespace Kaula\TelegramBundle\Telegram\Command;


class StartCommand extends AbstractCommand {

  static public $name = 'start';
  static public $description = 'Начать разговор с ботом';
  static public $visible = FALSE;
  static public $required_permissions = ['execute command start'];

  /**
   * Executes command.
   */
  public function execute() {
    $this->replyWithMessage('Привет! Список команд доступен по команде /help');

    // Execute /help
    $this->getBus()->executeCommand('help', $this->getUpdate());
  }




}