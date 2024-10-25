<?php
namespace Smp\tests\User;

use Smp\User\User;
use Smp\User\Factory as UserFactory;
use Smp\User\Database as UserDatabase;

use Smp\tests\BaseTest;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\DependsExternal;

#[CoversClass(UserDatabase::class)]
#[CoversFunction('__construct')]
#[CoversFunction('getNextId')]
#[CoversFunction('add')]
#[CoversFunction('get')]
#[CoversFunction('update')]
#[CoversFunction('delete')]
final class DatabaseTest extends BaseTest {
  
  // TODO Uncomment when tests are coded.
  // #[DependsExternal(FactoryTest::class, 'testCreate')]
  #[DataProviderExternal(UserDataProvider::class, 'userObjectProvider')]
  public function testCreate($user) : void {
    throw new \Exception('TODO Implement.');
  }
  
}
