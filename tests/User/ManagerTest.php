<?php declare(strict_types=1);
/**
 * @file
 * User Manager test file.
 */

namespace Smp\tests\User;

use Smp\User\Manager;

use Smp\tests\BaseTest;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(Manager::class)]
#[CoversFunction('__construct')]
#[CoversFunction('get')]
#[CoversFunction('create')]
#[CoversFunction('update')]
#[CoversFunction('delete')]
final class ManagerTest extends BaseTest {
  
  public static function userProvider() : array {
    $context = ['a', 'b'];
    return [
      // user, password, context
      ['user1', '123', $context],
      ['user2', '456', $context],
      ['user3', 'qwerty', $context],
      ['user3', NULL, $context],
    ];
  }
  
  #[DataProvider('userProvider')]
  public function testCreate($username, $password, $context) : void {
    $manager = new Manager();
    
    $user = $manager->create($username, $password, $context);
    
    $this->assertIsInt($user->getId(), "New user id is an int.");
    $this->assertSame(1, $user->getId(), "New user has the correct id.");
    $this->assertSame($username, $user->getUsername(), "New user has the correct username.");
    
    $new_context = $user->getContext();
    
    $this->assertEquals($context, $new_context, "New user has the correct context.");
  }
  
  public function testPassword() : void {
    $users = static::userProvider();
    
    $manager = new Manager();
    
    foreach ($users as $new_user_info) {
      $manager->create($new_user_info[0], $new_user_info[1], $new_user_info[2]);
    }
    
    foreach ($users as $id => $new_user_info) {
      $uid = $id + 1;
      
      $this->assertSame($new_user_info[1], $manager->getPass($uid), "User password is correct.");
    }
    
    $manager->delete(1);
    
    $this->assertFalse($manager->getPass(1), "Deleted user has no password.");
  }
  
  public function testCreateMultiple() : void {
    $users = static::userProvider();
    
    $manager = new Manager();
    
    foreach ($users as $new_user_info) {
      $manager->create($new_user_info[0], $new_user_info[1], $new_user_info[2]);
    }
    
    foreach ($users as $id => $new_user_info) {
      $uid = $id + 1;
      
      $user = $manager->get($uid);
      
      $this->assertSame($uid, $user->getId(), "New user has the correct id.");
      $this->assertSame($new_user_info[0], $user->getUsername(), "New user has the correct username.");
      $this->assertSame($new_user_info[1], $manager->getPass($uid), "User password is correct.");
      
      $new_context = $user->getContext();
      
      $this->assertEquals($new_user_info[2], $new_context, "New user has the correct context.");
    }
  }
  
  public function testUpdate() {
    $context = ['a', 'b'];
    $manager = new Manager();
    
    $user = $manager->create("user1", "123");
    
    $new_user = $manager->update($user->getId(), "user2", "456", $context);
    
    $this->assertIsObject($new_user, "Returned updated user is an object.");
    $this->assertNotEquals($new_user, $user, "Returned updated user is not the same object as the old user.");
    $this->assertSame($user->getId(), $new_user->getId(), "Returned updated user has the correct id.");
    $this->assertSame("user2", $new_user->getUsername(), "Returned updated user has the correct username.");
    $this->assertSame("456", $manager->getPass($new_user->getId()), "Returned updated user has the correct password.");
    $this->assertSame($context, $new_user->getContext(), "Returned updated user has the correct context.");
    
    unset($new_user);
    
    $new_user = $manager->get(1);
    
    $this->assertIsObject($new_user, "Updated user is an object.");
    $this->assertNotEquals($new_user, $user, "Updated user is not the same object as the old user.");
    $this->assertSame($user->getId(), $new_user->getId(), "Updated user has the correct id.");
    $this->assertSame("user2", $new_user->getUsername(), "Updated user has the correct username.");
    $this->assertSame("456", $manager->getPass($new_user->getId()), "Returned updated user has the correct password.");
    $this->assertSame($context, $new_user->getContext(), "Updated user has the correct context.");
  }
}

























