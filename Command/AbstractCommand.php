<?php
/**
 * Created by PhpStorm.
 * User: ant
 * Date: 17.03.2017
 * Time: 20:02
 */

namespace Kaula\TelegramBundle\Command;


use Exception;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Stopwatch\Stopwatch;

abstract class AbstractCommand extends ContainerAwareCommand {

  use LockableTrait;

  const LOCK_TELEGRAM_OPERATIONS = 'telegram_operations';

  const RESULT_OK = 0;
  const RESULT_EXCEPTION = 1;
  const RESULT_ALREADY_RUNNING = 2;

  /**
   * @var int
   */
  protected $exit_code;

  /**
   * @var InputInterface
   */
  protected $input;

  /**
   * @var OutputInterface
   */
  protected $output;

  /**
   * @var SymfonyStyle
   */
  protected $io;

  /**
   * @var Stopwatch
   */
  protected $stopwatch;

  /**
   * Whether lock is blocking or not
   *
   * @var bool
   */
  protected $blocking = FALSE;

  /**
   * @var array
   */
  protected $config;

  /**
   * @param \Symfony\Component\Console\Input\InputInterface $input
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   */
  protected function initialize(InputInterface $input, OutputInterface $output) {
    $this->stopwatch = new Stopwatch();
    $this->stopwatch->start('initialized');
    $this->input = $input;
    $this->output = $output;
    $this->io = new SymfonyStyle($input, $output);
    $this->exit_code = self::RESULT_OK;
    // Get configuration
    $this->config = $this->getContainer()->getParameter('kettari_telegram');
  }

  /**
   * Executes the current command.
   *
   * @param \Symfony\Component\Console\Input\InputInterface $input
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   * @return int|null null or 0 if everything went fine, or an error code
   *
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    // Start the timer and acquire the lock
    $this->stopwatch->start('execute');
    $c = $this->getContainer();

    try {
      // Log execution
      $c->get('logger')
        ->info('Executing {command_name} command',
          ['command_name' => $this->getName()]);
      // Acquire lock. We can not move further without lock to avoid race conditions
      if (!$this->lock(self::LOCK_TELEGRAM_OPERATIONS, $this->isBlocking())) {
        $this->io->warning('Unable to acquire lock. It seems another TelegramBundle command is currently running.');
        $this->exit_code = self::RESULT_ALREADY_RUNNING;

        return $this->exit_code;
      }
      if ($this->output->isVerbose()) {
        $this->io->writeln('Acquired lock');
        $this->io->writeln('Environment: '.
          $c->getParameter('kernel.environment'));
      }

      // Execute command
      $this->executeCommand();

      // Release the lock
      $this->release();
      if ($this->output->isVerbose()) {
        $this->io->writeln('Lock released');
      }
    } catch (Exception $e) {
      $message = sprintf('%s: %s (uncaught exception) at %s line %s while running console command',
        get_class($e), $e->getMessage(), $e->getFile(), $e->getLine());
      // Log exception to console
      $this->io->error($message);
      // Log exception
      $c->get('logger')
        ->error($message, ['exception' => $e]);

      $this->exit_code = self::RESULT_EXCEPTION;
    } finally {
      // Stop timer and write information and log
      $event = $this->stopwatch->stop('execute');
      $execution_time = $event->getDuration() / 1000;
      $memory_peak = $event->getMemory() / 1024 / 1024;
      if ($this->output->isVerbose()) {
        $this->io->writeln(sprintf('Command finished in %.2f seconds',
          $execution_time));
      }
      if ($this->output->isVerbose()) {
        $this->io->writeln(sprintf('Peak memory usage %.2f MB', $memory_peak));
      }
      $c->get('logger')
        ->debug('{command_name} finished in {execution_time} seconds, peak memory usage {memory_peak} MB',
          [
            'command_name'   => $this->getName(),
            'execution_time' => sprintf('%.2f', $execution_time),
            'memory_peak'    => sprintf('%.2f', $memory_peak),
          ]);

      $this->exitCodeLog();
    }

    return $this->exit_code;
  }

  /**
   * Execute actual code of the command.
   */
  protected function executeCommand() {
  }

  /**
   * Append log with exit code information
   */
  protected function exitCodeLog() {
    $this->getContainer()
      ->get('logger')
      ->info('{command_name} finished with exit code {exit_code}', [
        'command_name' => $this->getName(),
        'exit_code'    => $this->exit_code,
      ]);
  }

  /**
   * @return bool
   */
  public function isBlocking(): bool {
    return $this->blocking;
  }

  /**
   * @param bool $blocking
   * @return AbstractCommand
   */
  public function setBlocking(bool $blocking): AbstractCommand {
    $this->blocking = $blocking;

    return $this;
  }

}