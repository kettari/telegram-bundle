<?php
/**
 * Created by PhpStorm.
 * User: ant
 * Date: 15.03.2017
 * Time: 18:14
 */

namespace Kaula\TelegramBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use unreal4u\TelegramAPI\Telegram\Methods\SetWebhook;
use unreal4u\TelegramAPI\TgLog;

class SetWebhookCommand extends ContainerAwareCommand {

  const URL_TEMPLATE = 'https://sentinel/app_dev.php';

  /**
   * Configures the current command.
   */
  protected function configure() {
    $this->setName('telegram:webhook-set')
      ->setDescription('Sets Telegram webhook.')
      ->setHelp('Installs Telegram webhook so we can start receiving updates.');
  }

  /**
   * @param \Symfony\Component\Console\Input\InputInterface $input
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   */
  protected function initialize(InputInterface $input, OutputInterface $output) {

  }

  /**
   * @param \Symfony\Component\Console\Input\InputInterface $input
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   * @return int|null|void
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $set_webhook = new SetWebhook();
    $set_webhook->url = self::URL_TEMPLATE.'/api/v1/'.$this->getContainer()
        ->getParameter('telegram.secret').'/webhook';

    $tgLog = new TgLog($this->getContainer()
      ->getParameter('telegram.token'));
    $tgLog->performApiRequest($set_webhook);
  }


}