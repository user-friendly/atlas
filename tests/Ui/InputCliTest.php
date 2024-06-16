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
  // #[DataProvider('userInputValidProvider')]
  public function testUserInputValid(/* $input */) : void {
    $inputCli = new InputCli();
    
    $e = \chr(0x07);
    
    $input = "test \"arg1\" arg2;cmd2 'asdf'\ncmd3 `lkjsdf sd      \tsdf`";
    $input = ";;\n;\ncmd1 \"arg1 \t arg1.5\" arg2 `arg3` arg4 arg5' \t\t some weird arg is #5 \t  'extra \t\t  ";
    $input .= 'arg6\\"\\\'\\ ' . "\\\tspace End   \t\t    ";
    $input .= "\ncmd2 arg21;cmd3 arg31";
    
    $input = "klsdf'sfds sdf ds     ;  dsfdsfds';;;;;\n\n\n\n   \n\n\t\t\t\n\n\n;;;sdf;test;go backwards";
    
    $tokens = $inputCli->process($input);
    
    var_dump($tokens);
  }
}



























