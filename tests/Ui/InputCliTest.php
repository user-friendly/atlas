<?php declare(strict_types=1);
/**
 * @file
 * 
 * InputCliTest class file.
 */

namespace Smp\tests\Ui;

use Smp\tests\BaseTest;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\DataProvider;

use Smp\Ui\InputCli;

#[CoversClass(InputCli::class)]
#[CoversFunction('process')]
#[CoversFunction('getResult')]
final class InputCliTest extends BaseTest {
  
  /**
   * Provides valid user input test data.
   * 
   * @return array
   */
  public static function userInputValidProvider() : array {
    $inputs = [
      ["talk", ],
      ["rename", ],
      ["quit", ],
    ];
    return $inputs;
  }
  
  /**
   * Tests valid user input only.
   */
  // #[DataProvider('userInputValidProvider')]
  public function testUserInputValid(/* $input */) : void {
    $inputCli = new InputCli();
    
    throw new \Exception("TODO Implement test.");
  }
}



























