<?php
/**
 * Created by PhpStorm.
 * User: ant
 * Date: 15.03.2017
 * Time: 18:14
 */

namespace Kaula\TelegramBundle\Command;





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

    $bot = $this->getContainer()
      ->get('telegram_bot');
    $bot->bumpQueue();

    $this->io->success('Queue bumped');
  }


}