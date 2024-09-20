<?php declare(strict_types=1);
/**
 * @file
 * 
 * InputCliTest class file.
 */

namespace Smp\tests\Ui\Command;

use Smp\tests\BaseTest;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\DataProvider;

use Smp\Ui\Command\Tokenizer;

#[CoversClass(Tokenizer::class)]
#[CoversFunction('process')]
final class TokenizerTest extends BaseTest {
  use TokenizerTestData;
  
  /**
   * Tests valid user input only.
   */
  #[DataProvider('userInputValidProvider')]
  public function testUserInputValid($input, $expected) : void {
    $tokenizer = new Tokenizer();
    
    $tokens = $tokenizer->process($input);
    
    $this->assertIsArray($tokens, "Tokenized output is an array.");
    $this->assertSameSize($expected, $tokens, "Number of tokenized commands match.");
    
    $this->assertEquals($expected, $tokens, "Tokenized commands match expected value.");
  }
  
  /**
   * Tests invalid user input only.
   */
  #[DataProvider('userInputInvalidProvider')]
  public function testUserInputInvalid($input) : void {
    throw new \Exception('TODO Implement invalid input tests.');
  }
}
