<?php
/**
 * Created by PhpStorm.
 * User: ant
 * Date: 16.03.2017
 * Time: 18:15
 */

namespace Kaula\TelegramBundle\Telegram\Command;


use Kaula\TelegramBundle\Telegram\CommandBus;
use unreal4u\TelegramAPI\Telegram\Types\Update;

abstract class AbstractCommand {

  /**
   * Command name.
   *
   * @var string
   */
  static public $name = NULL;

  /**
   * Command description.
   *
   * @var string
   */
  static public $description = NULL;

  /**
   * If this command is showed in /help?
   *
   * @var bool
   */
  protected $visible = TRUE;

  /**
   * @var CommandBus
   */
  protected $bus;

  /**
   * @var Update
   */
  protected $update;

  /**
   * AbstractCommand constructor.
   *
   * @param \Kaula\TelegramBundle\Telegram\CommandBus $bus
   * @param \unreal4u\TelegramAPI\Telegram\Types\Update $update
   */
  public function __construct(CommandBus $bus, Update $update) {
    $this->bus = $bus;
    $this->update = $update;
  }

  /**
   * Executes command.
   *
   * @return mixed
   */
  abstract public function execute();

  /**
   *
   */
  protected function replyWithMessage() {

  }

  /**
   * @return bool
   */
  public function isVisible(): bool {
    return $this->visible;
  }

  /**
   * @param bool $visible
   * @return AbstractCommand
   */
  public function setVisible(bool $visible): AbstractCommand {
    $this->visible = $visible;

    return $this;
  }

  /**
   * @return \Kaula\TelegramBundle\Telegram\CommandBus
   */
  public function getBus(): CommandBus {
    return $this->bus;
  }

  /**
   * @return \unreal4u\TelegramAPI\Telegram\Types\Update
   */
  public function getUpdate(): Update {
    return $this->update;
  }

}