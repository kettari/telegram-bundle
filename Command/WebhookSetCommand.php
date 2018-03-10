<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Command;


use unreal4u\TelegramAPI\Telegram\Methods\SetWebhook;
use unreal4u\TelegramAPI\Telegram\Types\Custom\InputFile;
use unreal4u\TelegramAPI\TgLog;

class WebhookSetCommand extends AbstractCommand
{

  /**
   * Configures the current command.
   */
  protected function configure()
  {
    $this->setName('telegram:webhook:set')
      ->setDescription('Sets Telegram webhook')
      ->setHelp(
        'Use this method to specify a url and receive incoming updates via an outgoing webhook. Whenever there is an update for the bot, we will send an HTTPS POST request to the specified url, containing a JSON-serialized Update. In case of an unsuccessful request, we will give up after a reasonable amount of attempts.'
      );
  }

  /**
   * Execute actual code of the command.
   */
  protected function executeCommand()
  {
    $setWebhook = new SetWebhook();
    $setWebhook->url = str_replace(
      '{secret}',
      $this->config['secret'],
      $this->config['url']
    );
    $this->io->writeln('Setting telegram webhook to URL: '.$setWebhook->url);

    $certificateFile = $this->config['certificate_file'] ?? null;
    if (!is_null($certificateFile)) {
      $setWebhook->certificate = new InputFile(
        $this->config['certificate_file']
      );
      if ($this->output->isVerbose()) {
        $this->io->writeln(
          'Certificate file: '.$this->config['certificate_file']
        );
      }
    }

    // Create API object and execute method
    $tgLog = new TgLog(
      $this->config['api_token'],
      $this->getContainer()
        ->get('logger')
    );
    $tgLog->performApiRequest($setWebhook);

    $this->io->success('Webhook set');
  }


}