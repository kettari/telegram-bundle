<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Command;


class QueueBumpCommand extends AbstractCommand
{

  /**
   * Configures the current command.
   */
  protected function configure()
  {
    $this->setName('telegram:queue:bump')
      ->setDescription('Send queued messages')
      ->setHelp(
        'Messages for massive push are queued in the database. This command sends part of these messages.'
      );
  }

  /**
   * Execute actual code of the command.
   */
  protected function executeCommand()
  {
    $this->io->writeln('Bumping the queue');

    $pusher = $this->getContainer()
      ->get('kettari_telegram.pusher');
    $pusher->bumpQueue();

    $this->io->success('Queue bumped');
  }


}