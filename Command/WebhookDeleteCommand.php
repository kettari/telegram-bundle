<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Command;


use unreal4u\TelegramAPI\Telegram\Methods\DeleteWebhook;
use unreal4u\TelegramAPI\TgLog;

class WebhookDeleteCommand extends AbstractCommand
{

  /**
   * Configures the current command.
   */
  protected function configure()
  {
    $this->setName('telegram:webhook:delete')
      ->setDescription('Deletes Telegram webhook')
      ->setHelp(
        'Use this method to remove webhook integration if you decide to switch back to getUpdates.'
      );
  }

  /**
   * Execute actual code of the command.
   */
  protected function executeCommand()
  {
    $deleteWebhook = new DeleteWebhook();

    $this->io->writeln('Deleting telegram webhook');

    // Create API object and execute method
    $tgLog = new TgLog(
      $this->config['api_token'],
      $this->getContainer()
        ->get('logger')
    );
    $tgLog->performApiRequest($deleteWebhook);

    $this->io->success('Webhook deleted');
  }


}