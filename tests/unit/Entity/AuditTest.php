<?php

namespace Entity;

use \DateTimeInterface;
use Kettari\TelegramBundle\Entity\Audit;
use Kettari\TelegramBundle\Entity\Chat;
use Kettari\TelegramBundle\Entity\User;
use PHPUnit\Framework\TestResult;

class AuditTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    /**
    * @var Audit
    */
    private $audit;
    
    protected function _before()
    {
        $this->audit = new Audit();

    }

    protected function _after()
    {
        $this->audit = null;
    }

    // tests
    public function testId()
    {
        $this->tester->assertNull($this->audit->getId());
    }

    public function testCreated()
    {
      $this->tester->assertInstanceOf(
        DateTimeInterface::class,
        $this->audit->getCreated()
      );
    }

    public function testType()
    {
      $type = 'my.custom.type';

      $this->tester->assertNull($this->audit->getType());
      $this->tester->assertSame($this->audit, $this->audit->setType($type));
      $this->tester->assertSame($type, $this->audit->getType());
    }

    public function testDescription()
    {
      $description = 'description';
      $this->tester->assertNull($this->audit->getDescription());
      $this->tester->assertSame(
        $this->audit,
        $this->audit->setDescription($description)
      );
      $this->tester->assertSame($description, $this->audit->getDescription());
    }

    public function testChat()
    {
      $this->tester->assertNull($this->audit->getChat());
      $chat = $this->make(Chat::class, []);
      $this->tester->assertSame(
        $this->audit,
        $this->audit->setChat($chat)
      );
      $this->tester->assertSame($chat, $this->audit->getChat());
    }

    public function testUser()
    {
      $this->tester->assertNull($this->audit->getUser());
      $user = $this->make(User::class, []);
      $this->tester->assertSame(
        $this->audit,
        $this->audit->setUser($user)
      );
      $this->tester->assertSame($user, $this->audit->getUser());
    }

    public function testContent()
    {

    }

    public function count()
    {
        return parent::count();
        // TODO: Implement count() method.
    }

    public function run(TestResult $result = null)
    {
        parent::run($result);
        // TODO: Implement run() method.
    }


}