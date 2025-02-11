<?php declare(strict_types=1);
/**
 * @file
 * User Manager test file.
 */

namespace Smp\tests\User;

use Smp\User\User;

use Smp\tests\BaseTest;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use Smp\User\UserState;

#[CoversClass(User::class)]
#[CoversFunction('__construct')]
#[CoversFunction('getId')]
#[CoversFunction('getUsername')]
#[CoversFunction('getContext')]
final class UserTest extends BaseTest {
  public function testUserNew() : void {
    $context = ['a', 'b'];
    $user = new User(1, "tester");
    
    $user->setPassword('123');
    $user->setContext($context);
    $user->setState(UserState::Game);
    
    $this->assertSame(1, $user->getId(), "New user has the correct id.");
    $this->assertSame("tester", $user->getUsername(), "New user has the correct username.");
    $this->assertSame("123", $user->getPassword(), "New user has the correct password.");
    
    $this->assertEquals($context, $user->getContext(), "New user has the correct context.");
    $this->assertEquals(UserState::Game, $user->getState(), "New user has the correct game state.");
  }
}
