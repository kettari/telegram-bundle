<?php
/**
 * Created by PhpStorm.
 * User: ant
 * Date: 25.04.2017
 * Time: 14:46
 */

namespace Kaula\TelegramBundle\Telegram\Subscriber;


use Doctrine\Bundle\DoctrineBundle\Registry;
use Kaula\TelegramBundle\Telegram\Bot;

abstract class AbstractBotSubscriber
{
  /**
   * @var Bot
   */
  private $bot;

  /**
   * AbstractBotSubscriber constructor.
   *
   * @param Bot $bot
   */
  public function __construct(Bot $bot)
  {
    $this->bot = $bot;
  }

  /**
   * @return Registry
   */
  public function getDoctrine(): Registry
  {
    return $this->getBot()
      ->getDoctrine();
  }

  /**
   * @return Bot
   */
  public function getBot(): Bot
  {
    return $this->bot;
  }

}