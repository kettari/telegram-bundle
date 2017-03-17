<?php
/**
 * Created by PhpStorm.
 * User: ant
 * Date: 15.03.2017
 * Time: 18:14
 */

namespace Kaula\TelegramBundle\Command;


use unreal4u\TelegramAPI\Telegram\Methods\GetWebhookInfo;
use unreal4u\TelegramAPI\Telegram\Types\WebhookInfo;
use unreal4u\TelegramAPI\TgLog;

class WebhookInfoCommand extends AbstractCommand {

  /**
   * Configures the current command.
   */
  protected function configure() {
    $this->setName('telegram:webhook-info')
      ->setDescription('Deletes Telegram webhook')
      ->setHelp('Use this method to remove webhook integration if you decide to switch back to getUpdates.');
  }

  /**
   * Execute actual code of the command.
   */
  protected function executeCommand() {
    $webhook_info = new GetWebhookInfo();

    $this->io->writeln('Getting webhook information');

    // Create API object and execute method
    $tgLog = new TgLog($this->config['api_token'], $this->getContainer()
      ->get('logger'));
    /** @var WebhookInfo $hook_info */
    $hook_info = $tgLog->performApiRequest($webhook_info);

    $this->io->section('Webhook information');
    $this->io->writeln('Url: '.
      (empty($hook_info->url) ? '(empty)' : $hook_info->url));
    $this->io->writeln('Has custom certificate: '.
      (($hook_info->has_custom_certificate) ? 'Yes' : 'No'));
    $this->io->writeln('Pending update count: '.
      $hook_info->pending_update_count);
    $this->io->writeln('Last error date: '.
      ($hook_info->last_error_date > 0 ? date('r',
        $hook_info->last_error_date) : '(none)'));
    $this->io->writeln('Last error message: '.
      (empty($hook_info->last_error_message) ? '(empty)' : $hook_info->last_error_message));
    $this->io->writeln('Max connections: '.
      (empty($hook_info->max_connections) ? '(none)' : $hook_info->max_connections));
    if (count($hook_info->allowed_updates)) {
      $allowed_updates = implode(', ', $hook_info->allowed_updates);
    } else {
      $allowed_updates = '(all)';
    }
    $this->io->writeln('Allowed updates: '.$allowed_updates);

    $this->io->success('Information received');
  }


}