<?php
/**
 * @file
 * 
 * TokenzierTestData trait file.
 */

namespace Smp\tests\Ui\Command;

trait TokenizerTestData {
  /**
   * Provides valid user input test data.
   *
   * @return array
   */
  public static function userInputValidProvider() : array {
    // User input.
    $inputs = [
      // Key is the untokenized command (i.e. a string), value is the tokenized command (i.e. expected value).
      
      "" => [],
      " " => [],
      "  " => [],
      "   " => [],
      
      "\t" => [],
      "\t\t" => [],
      "\t\t\t" => [],
      " \t" => [],
      "\t " => [],
      " \t" => [],
      "\t " => [],
      "  \t" => [],
      "\t  " => [],
      "   \t" => [],
      "\t   " => [],
      
      ";" => [],
      ";;" => [],
      " ; " => [],
      "\t;\t" => [],
      
      "look" => [["look"]],
      "look\n" => [["look"]],
      "look\r\n" => [["look"]],
      
      "   look   " => [["look"]],
      "\t\t\tlook\t\t\t" => [["look"]],
      "look west" => [["look", "west"]],
      "look\teast" => [["look", "east"]],
      "   look    west    " => [["look", "west"]],
      "\t\t\tlook\t\t\t\west\t\t\t" => [["look", "west"]],
      "setname \"Sir Testalot\"" => [["setname", "Sir Testalot"]],
      "setname 'Sir Testalot'" => [["setname", "Sir Testalot"]],
      
      "look;west" => [["look"], ["west"]],
      "look\nwest" => [["look"], ["west"]],
      "look\r\nwest" => [["look"], ["west"]],
      
      "look;west;attack" => [["look"], ["west"], ["attack"]],
      "look;west;attack all" => [["look"], ["west"], ["attack", "all"]],
      "go up;look at \"statue of Gorlok\"" => [["go", "up"], ["look", "at", "statue of Gorlok"]],
      "open door;;go east;;" => [["open", "door"], ["go", "east"]],
      
      "strange`   `token 'this is a full sentance'_but_the_argument\" is not \"over" => [["strange   token", "this is a full sentance_but_the_argument is not over"]],
      
      // TODO Add more test cases.
    ];
    // Transform the input map to the test function's arguments.
    return \array_map(fn(string $input, array $expected) => [$input, $expected], \array_keys($inputs), \array_values($inputs));
  }
  
  public static function userInputInvalidProvider() : array {
    $inputs = [
      0, 1, -1, 1.5, [], new \stdClass(),
      "\'", "\"", "  \'  ", "  \"  ", "\t\t\'\t\t", "\t\t\"\t\t",
    ];
    return \array_map(fn(mixed $input) => [$input], $inputs);
  }
}
