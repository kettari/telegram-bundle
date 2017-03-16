<?php
/**
 * Created by PhpStorm.
 * User: ant
 * Date: 16.03.2017
 * Time: 18:18
 */

namespace Kaula\TelegramBundle\Telegram\Command;


class HelpCommand extends AbstractCommand {

  static public $name = 'help';
  static public $description = 'Показать список команд бота';

  /**
   * Executes command.
   */
  public function execute() {

  }


}