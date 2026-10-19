<?php
namespace Smp\tests\User;

use Smp\User\User;
use Smp\User\UserState;
use Smp\User\Factory as UserFactory;

final class UserDataProvider {
  private static $userContext = [
    'nickname' => 'John Doe',
    'email' => 'johndoe123@example.com',
  ];
  
  private static $rawUserData = [
    // user, password, context
    [User::MIN_ID, 'user1', '123', [], UserState::Game],
    [User::MIN_ID + 2, 'user2', '456', [], UserState::Game],
    [User::MIN_ID + 8, 'johndoe', 'qwe123', [], UserState::Login],
    [User::MIN_ID + 256, 'user3', 'qwerty', [], UserState::Game],
    [User::MIN_ID + 1024, 'user3', NULL, [], UserState::Login],
  ];
  
  /**
   * Hydrates context.
   */
  private static function getRawData() {
    return \array_map(function($raw) {
      $raw[3] = self::$userContext;
      return $raw;
    }, self::$rawUserData);
  }
  
  public static function getCount() {
    return \count(self::$rawUserData);
  }
  
  public static function userProvider() : array {
    return self::getRawData();
  }
  
  public static function userObjectProvider() : array {
    $factory = new UserFactory();
    return \array_map(fn($info) =>
      [$factory->create($info[0], $info[1], $info[2], $info[3], $info[4])],
      self::userProvider()
    );
  }
}

