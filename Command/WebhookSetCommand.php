<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Command;


use Kettari\TelegramBundle\Entity\BotSetting;
use unreal4u\TelegramAPI\Telegram\Methods\GetMe;
use unreal4u\TelegramAPI\Telegram\Methods\SetWebhook;
use unreal4u\TelegramAPI\Telegram\Types\Custom\InputFile;
use unreal4u\TelegramAPI\Telegram\Types\User;
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
    // Set webhook
    $this->setWebhook();
    // Fill self user ID
    $this->requestMe();
  }

  /**
   * Sets webhook.
   */
  private function setWebhook()
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

  /**
   * Executed getMe method.
   */
  private function requestMe()
  {
    $getMe = new GetMe();
    // Create API object and execute method
    $tgLog = new TgLog(
      $this->config['api_token'],
      $this->getContainer()
        ->get('logger')
    );
    $user = $tgLog->performApiRequest($getMe);

    if ($user instanceof User) {
      /** @var \Doctrine\Bundle\DoctrineBundle\Registry $d */
      $d = $this->getContainer()
        ->get('doctrine');
      if (is_null(
        $setting = $d->getRepository('KettariTelegramBundle:BotSetting')
          ->findOneByName('bot_user_id')
      )) {
        $setting = new BotSetting();
        $d->getManager()
          ->persist($setting);
      }

      // Assign self bot ID
      $setting->setName('bot_user_id')
        ->setValue((string)$user->id);

      $d->getManager()
        ->flush();

      $this->io->success(sprintf('Self bot user ID=%d saved', $user->id));

    } else {
      $this->io->error('GetMe method returned not a user object');
    }
  }


}