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

#[CoversClass(User::class)]
#[CoversFunction('__construct')]
#[CoversFunction('getId')]
#[CoversFunction('getUsername')]
#[CoversFunction('getContext')]
final class UserTest extends BaseTest {
  public function testUserNew() : void {
    $context = ['a', 'b'];
    $user = new User(1, "tester", $context);
    
    $this->assertSame(1, $user->getId(), "New user has the correct id.");
    $this->assertSame("tester", $user->getUsername(), "New user has the correct username.");
    
    $new_context = $user->getContext();
    
    $this->assertEquals($context, $new_context, "New user has the correct context.");
  }
}
