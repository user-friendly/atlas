<?php
/**
 * @file
 * 
 * generate_lut script file.
 */

namespace Smp\scripts;

define('DEBUG', TRUE);

require __DIR__ . '/../vendor/autoload.php';

echo "Generate lookup tables: \n";

function print_array_map($callback) {
  $output = "\n\n[";
  for ($i = 0; $i < 256; $i++) {
    $char = \chr($i);
    $hex = \bin2hex($char);
    $bool = $callback($char) ? "TRUE" : "FALSE";
    $output .= "0x{$hex} => {$bool}, ";
  }
  $output .= "]\n\n";
  
  return $output;
}

echo "Escape character: ";
echo print_array_map(fn($c) => $c === "\\");

echo "Newline characters: ";
echo print_array_map(fn($c) => $c === "\n" || $c === ";");

echo "Quotes characters: ";
echo print_array_map(fn($c) => $c === "\"" /* || $c === "'" || $c === "`" */);

echo "Whitespace characters:";
echo print_array_map(fn($c) => $c === " " || $c === "\t" || $c === "\r");

echo "Printable characters: ";
echo print_array_map("ctype_graph");
