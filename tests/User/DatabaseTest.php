<?php
namespace Smp\tests\User;

use Smp\User\User;
use Smp\User\UserState;
use Smp\User\Factory as UserFactory;
use Smp\User\Database as UserDatabase;

use Smp\tests\BaseTest;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\TestDox;

#[CoversClass(UserDatabase::class)]
final class DatabaseTest extends BaseTest {
  private static $factory;
  private static $admin;
  private static $db;
  
  public static function setUpBeforeClass(): void
  {
    self::$factory = new UserFactory();
    self::$db = new UserDatabase();
  }
  
  #[TestDox('Get next id.')]
  public function testNextId() {
    $id = self::$db->getNextId();
    $this->assertIsInt($id);
    $this->assertGreaterThan(0, $id);
  }
  
  #[TestDox('Add user to database.')]
  #[DataProviderExternal(UserDataProvider::class, 'userProvider')]
  public function testAdd($id, $username, $password, $context, $state) : void {
    $new_user = self::$factory->create($id, $username, $password, $context, $state);
    
    self::$db->add($new_user);
    $user = self::$db->get($id);
    
    $this->assertIsInt($user->getId(), "User id is an int.");
    $this->assertSame($id, $user->getId(), "User has the correct id.");
    $this->assertSame($username, $user->getUsername(), "User has the correct username.");
    $this->assertSame($password, $user->getPassword(), "User has the correct password.");
    $this->assertEquals($context, $user->getContext(), "User has the correct context.");
    $this->assertEquals($state, $user->getState(), "User has the correct game state.");
    
    $this->assertEquals($id + 1, self::$db->getNextId(), "Next user ID is correct.");
  }
  
  #[TestDox('Iterate over database.')]
  #[Depends('testAdd')]
  public function testIterate() : void {
    $test_set_size = UserDataProvider::getCount();
    $db_size = \count(self::$db);
    $this->assertEquals($test_set_size, $db_size);
    
    foreach (self::$db as $user) {
      $this->assertIsObject($user);
      $this->assertIsInt($user->getId());
    }
  }
  
  #[TestDox('Update user in database.')]
  #[Depends('testAdd')]
  #[DataProviderExternal(UserDataProvider::class, 'userProvider')]
  public function testUpdate($id, $username, $password, $context, $state) : void {
    self::$db->get($id);
    
    $user = self::$db->get($id);
    $this->assertSame($id, $user->getId());
    
    $new_username = "{$username}_v1";
    
    // Update external.
    $user->setUsername($new_username);
    
    $user = self::$db->get($id);
    
    $this->assertSame($id, $user->getId());
    $this->assertSame($new_username, $user->getUsername());
    
    $new_username = "{$username}_v2";
    $new_password = "pass_v2";
    $new_context = ["v2" => 2];
    $new_state = UserState::Game;
    
    $new_user = new User($user->getId(), $new_username);
    $new_user->setPassword($new_password)
      ->setContext($new_context)
      ->setState($new_state);
    
    $ret = self::$db->update($user->getId(), $new_user);
    
    $this->assertTrue(TRUE === $ret);
    
    $updated_user = self::$db->get($id);
    
    $this->assertIsInt($updated_user->getId(),);
    $this->assertSame($id, $updated_user->getId());
    $this->assertSame($new_username, $updated_user->getUsername());
    $this->assertSame($new_password, $updated_user->getPassword());
    $this->assertEquals($new_context, $updated_user->getContext());
    $this->assertEquals($new_state, $updated_user->getState());
  }
  
  #[TestDox('Delete non-existing user from database.')]
  public function testFalseDelete() : void {
    // MUST alwasy fail.
    $ret = self::$db->delete(self::$db->getNextId());
    $this->assertTrue(FALSE === $ret);
  }
  
  #[TestDox('Delete user from database.')]
  #[Depends('testUpdate')]
  #[DataProviderExternal(UserDataProvider::class, 'userProvider')]
  public function testDelete($id, $username, $password, $context, $state) : void {
    // FIXME This is kinda dumb.
    static $c = 0;
    if ($c++ % 2 === 1) {
      $ret = self::$db->delete($id);
    } else {
      $ret = self::$db->delete(
          self::$db->get($id)
        );
    }
    $this->assertTrue(TRUE === $ret);
  }
  
  #[TestDox('Verify database is empty.')]
  #[Depends('testDelete')]
  public function testDeleteDone() : void {
    $this->assertEmpty(self::$db);
  }
  
  
  public static function tearDownAfterClass(): void
  {
    self::$factory = NULL;
    self::$admin = NULL;
    self::$db = NULL;
  }
}
