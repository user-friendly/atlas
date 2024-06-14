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
      NULL, "",
      " ", "  ", "   ",
      "\t", "\t\t", "\t\t\t",
      " \t", "\t ", " \t", "\t ", "  \t", "\t  ", "   \t", "\t   ",
      ";", ";;", " ; ", "\t;\t",
      "look", "   look   ", "\t\t\tlook\t\t\t",
      "look west", "look\teast", "   look    west    ", "\t\t\tlook\t\t\t\west\t\t\t",
      "setname \"Sir Testalot\"",
      "setname 'Sir Testalot'",
      "look;west",
      "look;west;attack",
      "look;west;attack all",
      "go up;look at \"statue of Gorlok\"",
      "open door;;go east;;",
      // Add more test cases.
    ];
    \array_walk($inputs, function (&$input) {
      $input = [$input];
    });
    return $inputs;
  }
  
  public static function userInputInvalidProvider() : array {
    return [
      0, 1, -1, 1.5, [], new \stdClass(),
      "\'", "\"", "  \'  ", "  \"  ", "\t\t\'\t\t", "\t\t\"\t\t",
    ];
  }
  
  /**
   * Tests valid user input only.
   */
  #[DataProvider('userInputValidProvider')]
  public function testUserInputValid($input) : void {
    throw new \Exception("TODO Implement test.");
  }
}
