<?php
namespace Smp\tests\User;

use Smp\User\User;
use Smp\User\UserState;
use Smp\User\Factory as UserFactory;

use Smp\tests\BaseTest;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\DataProviderExternal;

#[CoversClass(UserFactory::class)]
#[CoversFunction('__construct')]
#[CoversFunction('create')]
#[CoversFunction('createTemporary')]
final class FactoryTest extends BaseTest {
  
  #[DataProviderExternal(UserDataProvider::class, 'userProvider')]
  public function testCreate($id, $username, $password, $context, $state) : void {
    $factory = new UserFactory();
    
    $user = $factory->create($id, $username, $password, $context, $state);
    
    $this->assertIsInt($user->getId(), "New user id is an int.");
    $this->assertSame($id, $user->getId(), "New user has the correct id.");
    $this->assertSame($username, $user->getUsername(), "New user has the correct username.");
    $this->assertSame($password, $user->getPassword(), "New user has the correct password.");
    $this->assertEquals($context, $user->getContext(), "New user has the correct context.");
    $this->assertEquals($state, $user->getState(), "New user has the correct game state.");
  }
  
  public function testCreateTemporary() : void {
    $factory = new UserFactory();
    
    $temp_user = $factory->createTemporary();
    
    $this->assertIsInt($temp_user->getId(), "Temp user id is an int.");
    $this->assertSame(0, $temp_user->getId(), "Temp user has the correct id.");
    $this->assertSame(UserFactory::TEMP_USERNAME, $temp_user->getUsername(), "Temp user has the correct username.");
    $this->assertNull($temp_user->getPassword(), "Temp user has NULL password.");
    $this->assertEmpty($temp_user->getContext(), "Temp user has an empty context.");
    $this->assertEquals(UserState::Login, $temp_user->getState(), "Temp user has the Login game state.");
  }
}
