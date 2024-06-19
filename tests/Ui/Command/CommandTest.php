<?php
/**
 * @file
 * 
 * CommandTest class file.
 */

namespace Smp\tests\Ui\Command;

use Smp\tests\BaseTest;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;

use Smp\Ui\Command\Command;

#[CoversClass(Command::class)]
#[CoversFunction('register')]
#[CoversFunction('registerAll')]
#[CoversFunction('create')]
#[CoversFunction('__construct')]
final class CommandTest extends BaseTest {
  
  public static function setUpBeforeClass() : void {
    // Register commands.
    Command::registerAll();
  }
  
  /**
   * Test registered commands.
   */
  public function testCommandRegistration() : void {
    // The following should now be available: quit, talk, rename
    $cmd = Command::create('quit');
    $this->assertNotNull($cmd, "Quit was created successfully.");
    $this->assertEquals('quit', $cmd->getId(), "Quit command has the correct id.");
    
    $cmd = Command::create('talk');
    $this->assertNotNull($cmd, "Talk was created successfully.");
    $this->assertEquals('talk', $cmd->getId(), "Talk command has the correct id.");
    
    $cmd = Command::create('rename');
    $this->assertNotNull($cmd, "Rename was created successfully.");
    $this->assertEquals('rename', $cmd->getId(), "Rename command has the correct id.");
    
    // Create by aliases.
    $cmd = Command::create('exit');
    $this->assertNotNull($cmd, "Quit command's alias `exit` works.");
    
    $cmd = Command::create('logout');
    $this->assertNotNull($cmd, "Quit command's alias `logout` works.");
    
    $cmd = Command::create('disconnect');
    $this->assertNotNull($cmd, "Quit command's alias `disconnect` works.");
    
    $cmd = Command::create('say');
    $this->assertNotNull($cmd, "Talk command's alias `say` works.");
    
    $cmd = Command::create('speak');
    $this->assertNotNull($cmd, "Quit command's alias `speak` works.");
  }
  
  public function testCommandArguments() {
    $channel = 'global';
    $message = 'Hello World!';
    $args = [$channel, $message];
    
    $cmd = Command::create('talk');
    $this->assertNotNull($cmd, "Talk was created successfully.");
    
    $cmd->setArguments($args);
    $cmd_args = $cmd->getArguments();
    
    $this->assertIsArray($cmd_args, "Arguments are an array.");
    $this->assertEquals($channel, $cmd_args[0], "Talk command's first argument is `{$channel}.");
    $this->assertEquals($message, $cmd_args[1], "Talk command's second argument is `{$message}.");
  }
  
  public function testCommandContext() {
    $user = new \stdClass();
    $user->id = 1;
    $context = ['user' => $user];
    
    $cmd = Command::create('rename');
    $this->assertNotNull($cmd, "Rename was created successfully.");
    
    $cmd->setContext($context);
    $cmd_context = $cmd->getContext();
    
    $this->assertIsArray($cmd_context, "Context is an array.");
    $this->assertArrayHasKey('user', $cmd_context, "Context has the `user` key.");
    $this->assertEquals($user->id, $cmd_context['user']->id, "User's id is of the correct value.");
  }
}
