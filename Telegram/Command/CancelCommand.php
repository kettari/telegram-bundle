<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram\Command;


use unreal4u\TelegramAPI\Telegram\Types\Update;

class CancelCommand extends AbstractCommand
{

  static public $name = 'cancel';
  static public $description = 'command.cancel.description';
  static public $visible = false;
  static public $requiredPermissions = ['execute command cancel'];

  /**
   * @inheritdoc
   */
  public function execute(Update $update, string $parameter = '')
  {
    // This command does nothing just tells "command cancelled"
    $this->replyWithMessage(
      $update,
      $this->trans->trans('command.cancel.done')
    );
  }


}