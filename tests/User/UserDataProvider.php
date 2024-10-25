<?php
namespace Smp\tests\User;

use Smp\User\UserState;
use Smp\User\Factory as UserFactory;

final class UserDataProvider {
  
  public static function userProvider() : array {
    $context = ['a', 'b'];
    return [
      // user, password, context
      [0, 'user1', '123', $context, UserState::Game],
      [1, 'user2', '456', $context, UserState::Game],
      [256, 'user3', 'qwerty', $context, UserState::Game],
      [1024, 'user3', NULL, $context, UserState::Login],
    ];
  }
  
  public static function userObjectProvider() : array {
    $factory = new UserFactory();
    return \array_map(fn($info) =>
      [$factory->create($info[0], $info[1], $info[2], $info[3], $info[4])],
      self::userProvider()
    );
  }
}

