<?php
/**
 * Created by PhpStorm.
 * User: ant
 * Date: 15.03.2017
 * Time: 18:14
 */

namespace Kaula\TelegramBundle\Command;




use unreal4u\TelegramAPI\Telegram\Methods\DeleteWebhook;

use unreal4u\TelegramAPI\TgLog;

class WebhookDeleteCommand extends AbstractCommand {

  /**
   * Configures the current command.
   */
  protected function configure() {
    $this->setName('telegram:webhook-delete')
      ->setDescription('Deletes Telegram webhook')
      ->setHelp('Use this method to remove webhook integration if you decide to switch back to getUpdates.');
  }

  /**
   * Execute actual code of the command.
   */
  protected function executeCommand() {
    $delete_webhook = new DeleteWebhook();

    $this->io->writeln('Deleting telegram webhook');

    // Create API object and execute method
    $tgLog = new TgLog($this->config['api_token'], $this->getContainer()
      ->get('logger'));
    $tgLog->performApiRequest($delete_webhook);

    $this->io->success('Webhook deleted');
  }


}