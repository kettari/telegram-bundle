<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Command;


use unreal4u\TelegramAPI\Telegram\Methods\GetWebhookInfo;
use unreal4u\TelegramAPI\Telegram\Types\WebhookInfo;
use unreal4u\TelegramAPI\TgLog;

class WebhookInfoCommand extends AbstractCommand {

  /**
   * Configures the current command.
   */
  protected function configure() {
    $this->setName('telegram:webhook:info')
      ->setDescription('Prints webhook information')
      ->setHelp('Use this method to get current webhook status.');
  }

  /**
   * Execute actual code of the command.
   */
  protected function executeCommand() {
    $webhookInfo = new GetWebhookInfo();

    $this->io->writeln('Getting webhook information');

    // Create API object and execute method
    $tgLog = new TgLog($this->config['api_token'], $this->getContainer()
      ->get('logger'));
    /** @var WebhookInfo $hookInfo */
    $hookInfo = $tgLog->performApiRequest($webhookInfo);

    $this->io->section('Webhook information');
    $this->io->writeln('Url: '.
      (empty($hookInfo->url) ? '(empty)' : $hookInfo->url));
    $this->io->writeln('Has custom certificate: '.
      (($hookInfo->has_custom_certificate) ? 'Yes' : 'No'));
    $this->io->writeln('Pending update count: '.
      $hookInfo->pending_update_count);
    $this->io->writeln('Last error date: '.
      ($hookInfo->last_error_date > 0 ? date('r',
        $hookInfo->last_error_date) : '(none)'));
    $this->io->writeln('Last error message: '.
      (empty($hookInfo->last_error_message) ? '(empty)' : $hookInfo->last_error_message));
    $this->io->writeln('Max connections: '.
      (empty($hookInfo->max_connections) ? '(none)' : $hookInfo->max_connections));
    if (count($hookInfo->allowed_updates)) {
      $allowed_updates = implode(', ', $hookInfo->allowed_updates);
    } else {
      $allowed_updates = '(all)';
    }
    $this->io->writeln('Allowed updates: '.$allowed_updates);

    $this->io->success('Information received');
  }


}