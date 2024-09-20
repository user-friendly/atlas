<?php
/**
 * @file
 * 
 * Tokenizer class file.
 */

namespace Smp\Ui\Command;

use Smp\Logger\LoggerTrait;
use Smp\Logger\LoggerFactory;

/**
 * TODO Describe the input syntax and what exactly it outputs.
 */
class Tokenizer {
  use LoggerTrait;
  
  const STATE_NEW = 0;
  const STATE_TOKEN = 1;
  const STATE_COMPLETE = 2;
  const STATE_WHITESPACE = 3;
  const STATE_QUOTE_OPEN = 4;
  const STATE_QUOTE_CLOSE = 5;
  const STATE_ESCAPE = 6;
  const STATE_COMMAND = 7;
  
  /**
   * A map of escape characters.
   * 
   * @see scripts/generate_lut.php
   *
   * Escaping characters applies only to special characters.
   * Currently, these are the quotes & whitespaces.
   *
   * Char list: \
   *
   * @var array<int, bool>
   */
  protected $escape = [0x00 => FALSE, 0x01 => FALSE, 0x02 => FALSE, 0x03 => FALSE, 0x04 => FALSE, 0x05 => FALSE, 0x06 => FALSE, 0x07 => FALSE, 0x08 => FALSE, 0x09 => FALSE, 0x0a => FALSE, 0x0b => FALSE, 0x0c => FALSE, 0x0d => FALSE, 0x0e => FALSE, 0x0f => FALSE, 0x10 => FALSE, 0x11 => FALSE, 0x12 => FALSE, 0x13 => FALSE, 0x14 => FALSE, 0x15 => FALSE, 0x16 => FALSE, 0x17 => FALSE, 0x18 => FALSE, 0x19 => FALSE, 0x1a => FALSE, 0x1b => FALSE, 0x1c => FALSE, 0x1d => FALSE, 0x1e => FALSE, 0x1f => FALSE, 0x20 => FALSE, 0x21 => FALSE, 0x22 => FALSE, 0x23 => FALSE, 0x24 => FALSE, 0x25 => FALSE, 0x26 => FALSE, 0x27 => FALSE, 0x28 => FALSE, 0x29 => FALSE, 0x2a => FALSE, 0x2b => FALSE, 0x2c => FALSE, 0x2d => FALSE, 0x2e => FALSE, 0x2f => FALSE, 0x30 => FALSE, 0x31 => FALSE, 0x32 => FALSE, 0x33 => FALSE, 0x34 => FALSE, 0x35 => FALSE, 0x36 => FALSE, 0x37 => FALSE, 0x38 => FALSE, 0x39 => FALSE, 0x3a => FALSE, 0x3b => FALSE, 0x3c => FALSE, 0x3d => FALSE, 0x3e => FALSE, 0x3f => FALSE, 0x40 => FALSE, 0x41 => FALSE, 0x42 => FALSE, 0x43 => FALSE, 0x44 => FALSE, 0x45 => FALSE, 0x46 => FALSE, 0x47 => FALSE, 0x48 => FALSE, 0x49 => FALSE, 0x4a => FALSE, 0x4b => FALSE, 0x4c => FALSE, 0x4d => FALSE, 0x4e => FALSE, 0x4f => FALSE, 0x50 => FALSE, 0x51 => FALSE, 0x52 => FALSE, 0x53 => FALSE, 0x54 => FALSE, 0x55 => FALSE, 0x56 => FALSE, 0x57 => FALSE, 0x58 => FALSE, 0x59 => FALSE, 0x5a => FALSE, 0x5b => FALSE, 0x5c => TRUE, 0x5d => FALSE, 0x5e => FALSE, 0x5f => FALSE, 0x60 => FALSE, 0x61 => FALSE, 0x62 => FALSE, 0x63 => FALSE, 0x64 => FALSE, 0x65 => FALSE, 0x66 => FALSE, 0x67 => FALSE, 0x68 => FALSE, 0x69 => FALSE, 0x6a => FALSE, 0x6b => FALSE, 0x6c => FALSE, 0x6d => FALSE, 0x6e => FALSE, 0x6f => FALSE, 0x70 => FALSE, 0x71 => FALSE, 0x72 => FALSE, 0x73 => FALSE, 0x74 => FALSE, 0x75 => FALSE, 0x76 => FALSE, 0x77 => FALSE, 0x78 => FALSE, 0x79 => FALSE, 0x7a => FALSE, 0x7b => FALSE, 0x7c => FALSE, 0x7d => FALSE, 0x7e => FALSE, 0x7f => FALSE, 0x80 => FALSE, 0x81 => FALSE, 0x82 => FALSE, 0x83 => FALSE, 0x84 => FALSE, 0x85 => FALSE, 0x86 => FALSE, 0x87 => FALSE, 0x88 => FALSE, 0x89 => FALSE, 0x8a => FALSE, 0x8b => FALSE, 0x8c => FALSE, 0x8d => FALSE, 0x8e => FALSE, 0x8f => FALSE, 0x90 => FALSE, 0x91 => FALSE, 0x92 => FALSE, 0x93 => FALSE, 0x94 => FALSE, 0x95 => FALSE, 0x96 => FALSE, 0x97 => FALSE, 0x98 => FALSE, 0x99 => FALSE, 0x9a => FALSE, 0x9b => FALSE, 0x9c => FALSE, 0x9d => FALSE, 0x9e => FALSE, 0x9f => FALSE, 0xa0 => FALSE, 0xa1 => FALSE, 0xa2 => FALSE, 0xa3 => FALSE, 0xa4 => FALSE, 0xa5 => FALSE, 0xa6 => FALSE, 0xa7 => FALSE, 0xa8 => FALSE, 0xa9 => FALSE, 0xaa => FALSE, 0xab => FALSE, 0xac => FALSE, 0xad => FALSE, 0xae => FALSE, 0xaf => FALSE, 0xb0 => FALSE, 0xb1 => FALSE, 0xb2 => FALSE, 0xb3 => FALSE, 0xb4 => FALSE, 0xb5 => FALSE, 0xb6 => FALSE, 0xb7 => FALSE, 0xb8 => FALSE, 0xb9 => FALSE, 0xba => FALSE, 0xbb => FALSE, 0xbc => FALSE, 0xbd => FALSE, 0xbe => FALSE, 0xbf => FALSE, 0xc0 => FALSE, 0xc1 => FALSE, 0xc2 => FALSE, 0xc3 => FALSE, 0xc4 => FALSE, 0xc5 => FALSE, 0xc6 => FALSE, 0xc7 => FALSE, 0xc8 => FALSE, 0xc9 => FALSE, 0xca => FALSE, 0xcb => FALSE, 0xcc => FALSE, 0xcd => FALSE, 0xce => FALSE, 0xcf => FALSE, 0xd0 => FALSE, 0xd1 => FALSE, 0xd2 => FALSE, 0xd3 => FALSE, 0xd4 => FALSE, 0xd5 => FALSE, 0xd6 => FALSE, 0xd7 => FALSE, 0xd8 => FALSE, 0xd9 => FALSE, 0xda => FALSE, 0xdb => FALSE, 0xdc => FALSE, 0xdd => FALSE, 0xde => FALSE, 0xdf => FALSE, 0xe0 => FALSE, 0xe1 => FALSE, 0xe2 => FALSE, 0xe3 => FALSE, 0xe4 => FALSE, 0xe5 => FALSE, 0xe6 => FALSE, 0xe7 => FALSE, 0xe8 => FALSE, 0xe9 => FALSE, 0xea => FALSE, 0xeb => FALSE, 0xec => FALSE, 0xed => FALSE, 0xee => FALSE, 0xef => FALSE, 0xf0 => FALSE, 0xf1 => FALSE, 0xf2 => FALSE, 0xf3 => FALSE, 0xf4 => FALSE, 0xf5 => FALSE, 0xf6 => FALSE, 0xf7 => FALSE, 0xf8 => FALSE, 0xf9 => FALSE, 0xfa => FALSE, 0xfb => FALSE, 0xfc => FALSE, 0xfd => FALSE, 0xfe => FALSE, 0xff => FALSE];
  
  /**
   * A map of newline delimiter characters.
   *
   * Char list: \n, ;
   *
   * @var array<int, bool>
   */
  protected $newLine = [0x00 => FALSE, 0x01 => FALSE, 0x02 => FALSE, 0x03 => FALSE, 0x04 => FALSE, 0x05 => FALSE, 0x06 => FALSE, 0x07 => FALSE, 0x08 => FALSE, 0x09 => FALSE, 0x0a => TRUE, 0x0b => FALSE, 0x0c => FALSE, 0x0d => FALSE, 0x0e => FALSE, 0x0f => FALSE, 0x10 => FALSE, 0x11 => FALSE, 0x12 => FALSE, 0x13 => FALSE, 0x14 => FALSE, 0x15 => FALSE, 0x16 => FALSE, 0x17 => FALSE, 0x18 => FALSE, 0x19 => FALSE, 0x1a => FALSE, 0x1b => FALSE, 0x1c => FALSE, 0x1d => FALSE, 0x1e => FALSE, 0x1f => FALSE, 0x20 => FALSE, 0x21 => FALSE, 0x22 => FALSE, 0x23 => FALSE, 0x24 => FALSE, 0x25 => FALSE, 0x26 => FALSE, 0x27 => FALSE, 0x28 => FALSE, 0x29 => FALSE, 0x2a => FALSE, 0x2b => FALSE, 0x2c => FALSE, 0x2d => FALSE, 0x2e => FALSE, 0x2f => FALSE, 0x30 => FALSE, 0x31 => FALSE, 0x32 => FALSE, 0x33 => FALSE, 0x34 => FALSE, 0x35 => FALSE, 0x36 => FALSE, 0x37 => FALSE, 0x38 => FALSE, 0x39 => FALSE, 0x3a => FALSE, 0x3b => TRUE, 0x3c => FALSE, 0x3d => FALSE, 0x3e => FALSE, 0x3f => FALSE, 0x40 => FALSE, 0x41 => FALSE, 0x42 => FALSE, 0x43 => FALSE, 0x44 => FALSE, 0x45 => FALSE, 0x46 => FALSE, 0x47 => FALSE, 0x48 => FALSE, 0x49 => FALSE, 0x4a => FALSE, 0x4b => FALSE, 0x4c => FALSE, 0x4d => FALSE, 0x4e => FALSE, 0x4f => FALSE, 0x50 => FALSE, 0x51 => FALSE, 0x52 => FALSE, 0x53 => FALSE, 0x54 => FALSE, 0x55 => FALSE, 0x56 => FALSE, 0x57 => FALSE, 0x58 => FALSE, 0x59 => FALSE, 0x5a => FALSE, 0x5b => FALSE, 0x5c => FALSE, 0x5d => FALSE, 0x5e => FALSE, 0x5f => FALSE, 0x60 => FALSE, 0x61 => FALSE, 0x62 => FALSE, 0x63 => FALSE, 0x64 => FALSE, 0x65 => FALSE, 0x66 => FALSE, 0x67 => FALSE, 0x68 => FALSE, 0x69 => FALSE, 0x6a => FALSE, 0x6b => FALSE, 0x6c => FALSE, 0x6d => FALSE, 0x6e => FALSE, 0x6f => FALSE, 0x70 => FALSE, 0x71 => FALSE, 0x72 => FALSE, 0x73 => FALSE, 0x74 => FALSE, 0x75 => FALSE, 0x76 => FALSE, 0x77 => FALSE, 0x78 => FALSE, 0x79 => FALSE, 0x7a => FALSE, 0x7b => FALSE, 0x7c => FALSE, 0x7d => FALSE, 0x7e => FALSE, 0x7f => FALSE, 0x80 => FALSE, 0x81 => FALSE, 0x82 => FALSE, 0x83 => FALSE, 0x84 => FALSE, 0x85 => FALSE, 0x86 => FALSE, 0x87 => FALSE, 0x88 => FALSE, 0x89 => FALSE, 0x8a => FALSE, 0x8b => FALSE, 0x8c => FALSE, 0x8d => FALSE, 0x8e => FALSE, 0x8f => FALSE, 0x90 => FALSE, 0x91 => FALSE, 0x92 => FALSE, 0x93 => FALSE, 0x94 => FALSE, 0x95 => FALSE, 0x96 => FALSE, 0x97 => FALSE, 0x98 => FALSE, 0x99 => FALSE, 0x9a => FALSE, 0x9b => FALSE, 0x9c => FALSE, 0x9d => FALSE, 0x9e => FALSE, 0x9f => FALSE, 0xa0 => FALSE, 0xa1 => FALSE, 0xa2 => FALSE, 0xa3 => FALSE, 0xa4 => FALSE, 0xa5 => FALSE, 0xa6 => FALSE, 0xa7 => FALSE, 0xa8 => FALSE, 0xa9 => FALSE, 0xaa => FALSE, 0xab => FALSE, 0xac => FALSE, 0xad => FALSE, 0xae => FALSE, 0xaf => FALSE, 0xb0 => FALSE, 0xb1 => FALSE, 0xb2 => FALSE, 0xb3 => FALSE, 0xb4 => FALSE, 0xb5 => FALSE, 0xb6 => FALSE, 0xb7 => FALSE, 0xb8 => FALSE, 0xb9 => FALSE, 0xba => FALSE, 0xbb => FALSE, 0xbc => FALSE, 0xbd => FALSE, 0xbe => FALSE, 0xbf => FALSE, 0xc0 => FALSE, 0xc1 => FALSE, 0xc2 => FALSE, 0xc3 => FALSE, 0xc4 => FALSE, 0xc5 => FALSE, 0xc6 => FALSE, 0xc7 => FALSE, 0xc8 => FALSE, 0xc9 => FALSE, 0xca => FALSE, 0xcb => FALSE, 0xcc => FALSE, 0xcd => FALSE, 0xce => FALSE, 0xcf => FALSE, 0xd0 => FALSE, 0xd1 => FALSE, 0xd2 => FALSE, 0xd3 => FALSE, 0xd4 => FALSE, 0xd5 => FALSE, 0xd6 => FALSE, 0xd7 => FALSE, 0xd8 => FALSE, 0xd9 => FALSE, 0xda => FALSE, 0xdb => FALSE, 0xdc => FALSE, 0xdd => FALSE, 0xde => FALSE, 0xdf => FALSE, 0xe0 => FALSE, 0xe1 => FALSE, 0xe2 => FALSE, 0xe3 => FALSE, 0xe4 => FALSE, 0xe5 => FALSE, 0xe6 => FALSE, 0xe7 => FALSE, 0xe8 => FALSE, 0xe9 => FALSE, 0xea => FALSE, 0xeb => FALSE, 0xec => FALSE, 0xed => FALSE, 0xee => FALSE, 0xef => FALSE, 0xf0 => FALSE, 0xf1 => FALSE, 0xf2 => FALSE, 0xf3 => FALSE, 0xf4 => FALSE, 0xf5 => FALSE, 0xf6 => FALSE, 0xf7 => FALSE, 0xf8 => FALSE, 0xf9 => FALSE, 0xfa => FALSE, 0xfb => FALSE, 0xfc => FALSE, 0xfd => FALSE, 0xfe => FALSE, 0xff => FALSE, ];
  
  /**
   * A map of quote characters.
   *
   * Char list: ", ', `
   *
   * @var array<int, bool>
   */
  protected $quote = [0x00 => FALSE, 0x01 => FALSE, 0x02 => FALSE, 0x03 => FALSE, 0x04 => FALSE, 0x05 => FALSE, 0x06 => FALSE, 0x07 => FALSE, 0x08 => FALSE, 0x09 => FALSE, 0x0a => FALSE, 0x0b => FALSE, 0x0c => FALSE, 0x0d => FALSE, 0x0e => FALSE, 0x0f => FALSE, 0x10 => FALSE, 0x11 => FALSE, 0x12 => FALSE, 0x13 => FALSE, 0x14 => FALSE, 0x15 => FALSE, 0x16 => FALSE, 0x17 => FALSE, 0x18 => FALSE, 0x19 => FALSE, 0x1a => FALSE, 0x1b => FALSE, 0x1c => FALSE, 0x1d => FALSE, 0x1e => FALSE, 0x1f => FALSE, 0x20 => FALSE, 0x21 => FALSE, 0x22 => TRUE, 0x23 => FALSE, 0x24 => FALSE, 0x25 => FALSE, 0x26 => FALSE, 0x27 => TRUE, 0x28 => FALSE, 0x29 => FALSE, 0x2a => FALSE, 0x2b => FALSE, 0x2c => FALSE, 0x2d => FALSE, 0x2e => FALSE, 0x2f => FALSE, 0x30 => FALSE, 0x31 => FALSE, 0x32 => FALSE, 0x33 => FALSE, 0x34 => FALSE, 0x35 => FALSE, 0x36 => FALSE, 0x37 => FALSE, 0x38 => FALSE, 0x39 => FALSE, 0x3a => FALSE, 0x3b => FALSE, 0x3c => FALSE, 0x3d => FALSE, 0x3e => FALSE, 0x3f => FALSE, 0x40 => FALSE, 0x41 => FALSE, 0x42 => FALSE, 0x43 => FALSE, 0x44 => FALSE, 0x45 => FALSE, 0x46 => FALSE, 0x47 => FALSE, 0x48 => FALSE, 0x49 => FALSE, 0x4a => FALSE, 0x4b => FALSE, 0x4c => FALSE, 0x4d => FALSE, 0x4e => FALSE, 0x4f => FALSE, 0x50 => FALSE, 0x51 => FALSE, 0x52 => FALSE, 0x53 => FALSE, 0x54 => FALSE, 0x55 => FALSE, 0x56 => FALSE, 0x57 => FALSE, 0x58 => FALSE, 0x59 => FALSE, 0x5a => FALSE, 0x5b => FALSE, 0x5c => FALSE, 0x5d => FALSE, 0x5e => FALSE, 0x5f => FALSE, 0x60 => TRUE, 0x61 => FALSE, 0x62 => FALSE, 0x63 => FALSE, 0x64 => FALSE, 0x65 => FALSE, 0x66 => FALSE, 0x67 => FALSE, 0x68 => FALSE, 0x69 => FALSE, 0x6a => FALSE, 0x6b => FALSE, 0x6c => FALSE, 0x6d => FALSE, 0x6e => FALSE, 0x6f => FALSE, 0x70 => FALSE, 0x71 => FALSE, 0x72 => FALSE, 0x73 => FALSE, 0x74 => FALSE, 0x75 => FALSE, 0x76 => FALSE, 0x77 => FALSE, 0x78 => FALSE, 0x79 => FALSE, 0x7a => FALSE, 0x7b => FALSE, 0x7c => FALSE, 0x7d => FALSE, 0x7e => FALSE, 0x7f => FALSE, 0x80 => FALSE, 0x81 => FALSE, 0x82 => FALSE, 0x83 => FALSE, 0x84 => FALSE, 0x85 => FALSE, 0x86 => FALSE, 0x87 => FALSE, 0x88 => FALSE, 0x89 => FALSE, 0x8a => FALSE, 0x8b => FALSE, 0x8c => FALSE, 0x8d => FALSE, 0x8e => FALSE, 0x8f => FALSE, 0x90 => FALSE, 0x91 => FALSE, 0x92 => FALSE, 0x93 => FALSE, 0x94 => FALSE, 0x95 => FALSE, 0x96 => FALSE, 0x97 => FALSE, 0x98 => FALSE, 0x99 => FALSE, 0x9a => FALSE, 0x9b => FALSE, 0x9c => FALSE, 0x9d => FALSE, 0x9e => FALSE, 0x9f => FALSE, 0xa0 => FALSE, 0xa1 => FALSE, 0xa2 => FALSE, 0xa3 => FALSE, 0xa4 => FALSE, 0xa5 => FALSE, 0xa6 => FALSE, 0xa7 => FALSE, 0xa8 => FALSE, 0xa9 => FALSE, 0xaa => FALSE, 0xab => FALSE, 0xac => FALSE, 0xad => FALSE, 0xae => FALSE, 0xaf => FALSE, 0xb0 => FALSE, 0xb1 => FALSE, 0xb2 => FALSE, 0xb3 => FALSE, 0xb4 => FALSE, 0xb5 => FALSE, 0xb6 => FALSE, 0xb7 => FALSE, 0xb8 => FALSE, 0xb9 => FALSE, 0xba => FALSE, 0xbb => FALSE, 0xbc => FALSE, 0xbd => FALSE, 0xbe => FALSE, 0xbf => FALSE, 0xc0 => FALSE, 0xc1 => FALSE, 0xc2 => FALSE, 0xc3 => FALSE, 0xc4 => FALSE, 0xc5 => FALSE, 0xc6 => FALSE, 0xc7 => FALSE, 0xc8 => FALSE, 0xc9 => FALSE, 0xca => FALSE, 0xcb => FALSE, 0xcc => FALSE, 0xcd => FALSE, 0xce => FALSE, 0xcf => FALSE, 0xd0 => FALSE, 0xd1 => FALSE, 0xd2 => FALSE, 0xd3 => FALSE, 0xd4 => FALSE, 0xd5 => FALSE, 0xd6 => FALSE, 0xd7 => FALSE, 0xd8 => FALSE, 0xd9 => FALSE, 0xda => FALSE, 0xdb => FALSE, 0xdc => FALSE, 0xdd => FALSE, 0xde => FALSE, 0xdf => FALSE, 0xe0 => FALSE, 0xe1 => FALSE, 0xe2 => FALSE, 0xe3 => FALSE, 0xe4 => FALSE, 0xe5 => FALSE, 0xe6 => FALSE, 0xe7 => FALSE, 0xe8 => FALSE, 0xe9 => FALSE, 0xea => FALSE, 0xeb => FALSE, 0xec => FALSE, 0xed => FALSE, 0xee => FALSE, 0xef => FALSE, 0xf0 => FALSE, 0xf1 => FALSE, 0xf2 => FALSE, 0xf3 => FALSE, 0xf4 => FALSE, 0xf5 => FALSE, 0xf6 => FALSE, 0xf7 => FALSE, 0xf8 => FALSE, 0xf9 => FALSE, 0xfa => FALSE, 0xfb => FALSE, 0xfc => FALSE, 0xfd => FALSE, 0xfe => FALSE, 0xff => FALSE, ];
  
  /**
   * A map of white space characters.
   *
   * Char list: " ", \t
   *
   * First character is just a space, quoted for doc clarity.
   *
   * @var array<int, bool>
   */
  protected $whiteSpace = [0x00 => FALSE, 0x01 => FALSE, 0x02 => FALSE, 0x03 => FALSE, 0x04 => FALSE, 0x05 => FALSE, 0x06 => FALSE, 0x07 => FALSE, 0x08 => FALSE, 0x09 => TRUE, 0x0a => FALSE, 0x0b => FALSE, 0x0c => FALSE, 0x0d => TRUE, 0x0e => FALSE, 0x0f => FALSE, 0x10 => FALSE, 0x11 => FALSE, 0x12 => FALSE, 0x13 => FALSE, 0x14 => FALSE, 0x15 => FALSE, 0x16 => FALSE, 0x17 => FALSE, 0x18 => FALSE, 0x19 => FALSE, 0x1a => FALSE, 0x1b => FALSE, 0x1c => FALSE, 0x1d => FALSE, 0x1e => FALSE, 0x1f => FALSE, 0x20 => TRUE, 0x21 => FALSE, 0x22 => FALSE, 0x23 => FALSE, 0x24 => FALSE, 0x25 => FALSE, 0x26 => FALSE, 0x27 => FALSE, 0x28 => FALSE, 0x29 => FALSE, 0x2a => FALSE, 0x2b => FALSE, 0x2c => FALSE, 0x2d => FALSE, 0x2e => FALSE, 0x2f => FALSE, 0x30 => FALSE, 0x31 => FALSE, 0x32 => FALSE, 0x33 => FALSE, 0x34 => FALSE, 0x35 => FALSE, 0x36 => FALSE, 0x37 => FALSE, 0x38 => FALSE, 0x39 => FALSE, 0x3a => FALSE, 0x3b => FALSE, 0x3c => FALSE, 0x3d => FALSE, 0x3e => FALSE, 0x3f => FALSE, 0x40 => FALSE, 0x41 => FALSE, 0x42 => FALSE, 0x43 => FALSE, 0x44 => FALSE, 0x45 => FALSE, 0x46 => FALSE, 0x47 => FALSE, 0x48 => FALSE, 0x49 => FALSE, 0x4a => FALSE, 0x4b => FALSE, 0x4c => FALSE, 0x4d => FALSE, 0x4e => FALSE, 0x4f => FALSE, 0x50 => FALSE, 0x51 => FALSE, 0x52 => FALSE, 0x53 => FALSE, 0x54 => FALSE, 0x55 => FALSE, 0x56 => FALSE, 0x57 => FALSE, 0x58 => FALSE, 0x59 => FALSE, 0x5a => FALSE, 0x5b => FALSE, 0x5c => FALSE, 0x5d => FALSE, 0x5e => FALSE, 0x5f => FALSE, 0x60 => FALSE, 0x61 => FALSE, 0x62 => FALSE, 0x63 => FALSE, 0x64 => FALSE, 0x65 => FALSE, 0x66 => FALSE, 0x67 => FALSE, 0x68 => FALSE, 0x69 => FALSE, 0x6a => FALSE, 0x6b => FALSE, 0x6c => FALSE, 0x6d => FALSE, 0x6e => FALSE, 0x6f => FALSE, 0x70 => FALSE, 0x71 => FALSE, 0x72 => FALSE, 0x73 => FALSE, 0x74 => FALSE, 0x75 => FALSE, 0x76 => FALSE, 0x77 => FALSE, 0x78 => FALSE, 0x79 => FALSE, 0x7a => FALSE, 0x7b => FALSE, 0x7c => FALSE, 0x7d => FALSE, 0x7e => FALSE, 0x7f => FALSE, 0x80 => FALSE, 0x81 => FALSE, 0x82 => FALSE, 0x83 => FALSE, 0x84 => FALSE, 0x85 => FALSE, 0x86 => FALSE, 0x87 => FALSE, 0x88 => FALSE, 0x89 => FALSE, 0x8a => FALSE, 0x8b => FALSE, 0x8c => FALSE, 0x8d => FALSE, 0x8e => FALSE, 0x8f => FALSE, 0x90 => FALSE, 0x91 => FALSE, 0x92 => FALSE, 0x93 => FALSE, 0x94 => FALSE, 0x95 => FALSE, 0x96 => FALSE, 0x97 => FALSE, 0x98 => FALSE, 0x99 => FALSE, 0x9a => FALSE, 0x9b => FALSE, 0x9c => FALSE, 0x9d => FALSE, 0x9e => FALSE, 0x9f => FALSE, 0xa0 => FALSE, 0xa1 => FALSE, 0xa2 => FALSE, 0xa3 => FALSE, 0xa4 => FALSE, 0xa5 => FALSE, 0xa6 => FALSE, 0xa7 => FALSE, 0xa8 => FALSE, 0xa9 => FALSE, 0xaa => FALSE, 0xab => FALSE, 0xac => FALSE, 0xad => FALSE, 0xae => FALSE, 0xaf => FALSE, 0xb0 => FALSE, 0xb1 => FALSE, 0xb2 => FALSE, 0xb3 => FALSE, 0xb4 => FALSE, 0xb5 => FALSE, 0xb6 => FALSE, 0xb7 => FALSE, 0xb8 => FALSE, 0xb9 => FALSE, 0xba => FALSE, 0xbb => FALSE, 0xbc => FALSE, 0xbd => FALSE, 0xbe => FALSE, 0xbf => FALSE, 0xc0 => FALSE, 0xc1 => FALSE, 0xc2 => FALSE, 0xc3 => FALSE, 0xc4 => FALSE, 0xc5 => FALSE, 0xc6 => FALSE, 0xc7 => FALSE, 0xc8 => FALSE, 0xc9 => FALSE, 0xca => FALSE, 0xcb => FALSE, 0xcc => FALSE, 0xcd => FALSE, 0xce => FALSE, 0xcf => FALSE, 0xd0 => FALSE, 0xd1 => FALSE, 0xd2 => FALSE, 0xd3 => FALSE, 0xd4 => FALSE, 0xd5 => FALSE, 0xd6 => FALSE, 0xd7 => FALSE, 0xd8 => FALSE, 0xd9 => FALSE, 0xda => FALSE, 0xdb => FALSE, 0xdc => FALSE, 0xdd => FALSE, 0xde => FALSE, 0xdf => FALSE, 0xe0 => FALSE, 0xe1 => FALSE, 0xe2 => FALSE, 0xe3 => FALSE, 0xe4 => FALSE, 0xe5 => FALSE, 0xe6 => FALSE, 0xe7 => FALSE, 0xe8 => FALSE, 0xe9 => FALSE, 0xea => FALSE, 0xeb => FALSE, 0xec => FALSE, 0xed => FALSE, 0xee => FALSE, 0xef => FALSE, 0xf0 => FALSE, 0xf1 => FALSE, 0xf2 => FALSE, 0xf3 => FALSE, 0xf4 => FALSE, 0xf5 => FALSE, 0xf6 => FALSE, 0xf7 => FALSE, 0xf8 => FALSE, 0xf9 => FALSE, 0xfa => FALSE, 0xfb => FALSE, 0xfc => FALSE, 0xfd => FALSE, 0xfe => FALSE, 0xff => FALSE, ];
  
  /**
   *
   * @var array<int, bool>
   */
  protected $graph = [0x00 => FALSE, 0x01 => FALSE, 0x02 => FALSE, 0x03 => FALSE, 0x04 => FALSE, 0x05 => FALSE, 0x06 => FALSE, 0x07 => FALSE, 0x08 => FALSE, 0x09 => FALSE, 0x0a => FALSE, 0x0b => FALSE, 0x0c => FALSE, 0x0d => FALSE, 0x0e => FALSE, 0x0f => FALSE, 0x10 => FALSE, 0x11 => FALSE, 0x12 => FALSE, 0x13 => FALSE, 0x14 => FALSE, 0x15 => FALSE, 0x16 => FALSE, 0x17 => FALSE, 0x18 => FALSE, 0x19 => FALSE, 0x1a => FALSE, 0x1b => FALSE, 0x1c => FALSE, 0x1d => FALSE, 0x1e => FALSE, 0x1f => FALSE, 0x20 => FALSE, 0x21 => TRUE, 0x22 => TRUE, 0x23 => TRUE, 0x24 => TRUE, 0x25 => TRUE, 0x26 => TRUE, 0x27 => TRUE, 0x28 => TRUE, 0x29 => TRUE, 0x2a => TRUE, 0x2b => TRUE, 0x2c => TRUE, 0x2d => TRUE, 0x2e => TRUE, 0x2f => TRUE, 0x30 => TRUE, 0x31 => TRUE, 0x32 => TRUE, 0x33 => TRUE, 0x34 => TRUE, 0x35 => TRUE, 0x36 => TRUE, 0x37 => TRUE, 0x38 => TRUE, 0x39 => TRUE, 0x3a => TRUE, 0x3b => TRUE, 0x3c => TRUE, 0x3d => TRUE, 0x3e => TRUE, 0x3f => TRUE, 0x40 => TRUE, 0x41 => TRUE, 0x42 => TRUE, 0x43 => TRUE, 0x44 => TRUE, 0x45 => TRUE, 0x46 => TRUE, 0x47 => TRUE, 0x48 => TRUE, 0x49 => TRUE, 0x4a => TRUE, 0x4b => TRUE, 0x4c => TRUE, 0x4d => TRUE, 0x4e => TRUE, 0x4f => TRUE, 0x50 => TRUE, 0x51 => TRUE, 0x52 => TRUE, 0x53 => TRUE, 0x54 => TRUE, 0x55 => TRUE, 0x56 => TRUE, 0x57 => TRUE, 0x58 => TRUE, 0x59 => TRUE, 0x5a => TRUE, 0x5b => TRUE, 0x5c => TRUE, 0x5d => TRUE, 0x5e => TRUE, 0x5f => TRUE, 0x60 => TRUE, 0x61 => TRUE, 0x62 => TRUE, 0x63 => TRUE, 0x64 => TRUE, 0x65 => TRUE, 0x66 => TRUE, 0x67 => TRUE, 0x68 => TRUE, 0x69 => TRUE, 0x6a => TRUE, 0x6b => TRUE, 0x6c => TRUE, 0x6d => TRUE, 0x6e => TRUE, 0x6f => TRUE, 0x70 => TRUE, 0x71 => TRUE, 0x72 => TRUE, 0x73 => TRUE, 0x74 => TRUE, 0x75 => TRUE, 0x76 => TRUE, 0x77 => TRUE, 0x78 => TRUE, 0x79 => TRUE, 0x7a => TRUE, 0x7b => TRUE, 0x7c => TRUE, 0x7d => TRUE, 0x7e => TRUE, 0x7f => FALSE, 0x80 => FALSE, 0x81 => FALSE, 0x82 => FALSE, 0x83 => FALSE, 0x84 => FALSE, 0x85 => FALSE, 0x86 => FALSE, 0x87 => FALSE, 0x88 => FALSE, 0x89 => FALSE, 0x8a => FALSE, 0x8b => FALSE, 0x8c => FALSE, 0x8d => FALSE, 0x8e => FALSE, 0x8f => FALSE, 0x90 => FALSE, 0x91 => FALSE, 0x92 => FALSE, 0x93 => FALSE, 0x94 => FALSE, 0x95 => FALSE, 0x96 => FALSE, 0x97 => FALSE, 0x98 => FALSE, 0x99 => FALSE, 0x9a => FALSE, 0x9b => FALSE, 0x9c => FALSE, 0x9d => FALSE, 0x9e => FALSE, 0x9f => FALSE, 0xa0 => FALSE, 0xa1 => FALSE, 0xa2 => FALSE, 0xa3 => FALSE, 0xa4 => FALSE, 0xa5 => FALSE, 0xa6 => FALSE, 0xa7 => FALSE, 0xa8 => FALSE, 0xa9 => FALSE, 0xaa => FALSE, 0xab => FALSE, 0xac => FALSE, 0xad => FALSE, 0xae => FALSE, 0xaf => FALSE, 0xb0 => FALSE, 0xb1 => FALSE, 0xb2 => FALSE, 0xb3 => FALSE, 0xb4 => FALSE, 0xb5 => FALSE, 0xb6 => FALSE, 0xb7 => FALSE, 0xb8 => FALSE, 0xb9 => FALSE, 0xba => FALSE, 0xbb => FALSE, 0xbc => FALSE, 0xbd => FALSE, 0xbe => FALSE, 0xbf => FALSE, 0xc0 => FALSE, 0xc1 => FALSE, 0xc2 => FALSE, 0xc3 => FALSE, 0xc4 => FALSE, 0xc5 => FALSE, 0xc6 => FALSE, 0xc7 => FALSE, 0xc8 => FALSE, 0xc9 => FALSE, 0xca => FALSE, 0xcb => FALSE, 0xcc => FALSE, 0xcd => FALSE, 0xce => FALSE, 0xcf => FALSE, 0xd0 => FALSE, 0xd1 => FALSE, 0xd2 => FALSE, 0xd3 => FALSE, 0xd4 => FALSE, 0xd5 => FALSE, 0xd6 => FALSE, 0xd7 => FALSE, 0xd8 => FALSE, 0xd9 => FALSE, 0xda => FALSE, 0xdb => FALSE, 0xdc => FALSE, 0xdd => FALSE, 0xde => FALSE, 0xdf => FALSE, 0xe0 => FALSE, 0xe1 => FALSE, 0xe2 => FALSE, 0xe3 => FALSE, 0xe4 => FALSE, 0xe5 => FALSE, 0xe6 => FALSE, 0xe7 => FALSE, 0xe8 => FALSE, 0xe9 => FALSE, 0xea => FALSE, 0xeb => FALSE, 0xec => FALSE, 0xed => FALSE, 0xee => FALSE, 0xef => FALSE, 0xf0 => FALSE, 0xf1 => FALSE, 0xf2 => FALSE, 0xf3 => FALSE, 0xf4 => FALSE, 0xf5 => FALSE, 0xf6 => FALSE, 0xf7 => FALSE, 0xf8 => FALSE, 0xf9 => FALSE, 0xfa => FALSE, 0xfb => FALSE, 0xfc => FALSE, 0xfd => FALSE, 0xfe => FALSE, 0xff => FALSE, ];
  
  /**
   * Default constructor.
   */
  public function __construct() {
    LoggerFactory::init();
    $this->logger = static::getLogger();
  }
  
  /**
   * Returns an array of tokenized commands.
   *
   * @param string $input
   * @return array<int, array<string>>
   */
  public function process(string $input) : array {
    $commands = [];
    $tokens = [];
    $token = "";
    
    $state = self::STATE_NEW;
    $stateNext = $state;
    
    // Current opening quote character.
    $quote = NULL;
    
    $i = 0;
    $len = \strlen($input);
    
    while ($i < $len) {
      // Get current character.
      $c = $input[$i];
      // Get the character's ASCII number.
      $d = \ord($c);
      
      switch ($state) {
        case self::STATE_NEW:
          
          if ($this->newLine[$d]) {
            // Advance pointer and ignore delimiter.
            $i++;
            // Finish the command tokenization.
            $stateNext = self::STATE_COMMAND;
          }
          elseif ($this->whiteSpace[$d]) {
            $stateNext = self::STATE_WHITESPACE;
          }
          elseif ($this->graph[$d]) {
            $stateNext = self::STATE_TOKEN;
          }
          else {
            throw new \RuntimeException("Invalid input character [{$d}] at position {$i}.");
          }
          
          break;
          
        case self::STATE_TOKEN:
          if ($this->escape[$d]) {
            // Advance pointer, ignoring the escape character.
            $i++;
            // Transition to the escape stete.
            $stateNext = self::STATE_ESCAPE;
          }
          elseif ($this->quote[$d]) {
            // Save the quote.
            $quote = $c;
            // Advance the pointer, so it won't be consumed later.
            $i++;
            
            // Start consuming all printable & whitespace characters.
            // The quote state is basically the token state,
            // but whitespace inclusive.
            $stateNext = self::STATE_QUOTE_OPEN;
          }
          // Exclude any special command delimiter characters.
          elseif ($this->graph[$d] && !$this->newLine[$d]) {
            // Consume printable characters.
            $token .= $c;
            // On consumption, advance the pointer.
            $i++;
            // State stays the same.
          }
          else {
            // Any other case, complete the token.
            $stateNext = self::STATE_COMPLETE;
          }
          break;
          
        case self::STATE_QUOTE_OPEN:
          if ($quote === $c) {
            $stateNext = self::STATE_QUOTE_CLOSE;
          }
          else {
            if ($this->graph[$d] || $this->whiteSpace[$d]) {
              // Consume printable & whitespace character.
              $token .= $c;
              // Advance the pointer.
              $i++;
            }
            else {
              // Anything else that isn't allowed in quotes will
              // yield to the token state.
              $stateNext = self::STATE_TOKEN;
              // Just for good measure, reset opening quote.
              $quote = NULL;
              
              // At this point, it's probably an invalid character.
              // Let the STATE_NEW decided.
            }
          }
          break;
          
        case self::STATE_QUOTE_CLOSE:
          // Ignore closing quote and transition to the token state.
          $i++;
          $stateNext = self::STATE_TOKEN;
          // Just for good measure, reset opening quote.
          $quote = NULL;
          break;
          
        case self::STATE_WHITESPACE:
          if ($this->whiteSpace[$d]) {
            // Ignore whitespace between tokens.
            $i++;
          }
          else {
            $stateNext = self::STATE_NEW;
          }
          break;
          
        case self::STATE_ESCAPE:
          if ($this->graph[$d] || $this->whiteSpace[$d]) {
            // Consume printable & whitespace character.
            $token .= $c;
            // Advance the pointer.
            $i++;
          }
          // Regardless, yield back to the token state. If an invalid character
          // was encountered, the token state will handle it.
          $stateNext = self::STATE_TOKEN;
          break;
          
        case self::STATE_COMPLETE:
          // Append the completed token to the list.
          if (\strlen($token)) {
            $tokens[] = $token;
          }
          $token = "";
          $stateNext = self::STATE_NEW;
          break;
          
        case self::STATE_COMMAND:
          if ($tokens) {
            $commands[] = $tokens;
            $tokens = [];
          }
          $stateNext = self::STATE_NEW;
          break;
          
        default:
          throw new \Exception("Invalid tokenizer state: {$state}");
          break;
      }
      
      // Make sure the state is updated at the end.
      $state = $stateNext;
    }
    
    if ($state === self::STATE_TOKEN || $state === self::STATE_NEW
        || $state === self::STATE_COMMAND || $state === self::STATE_WHITESPACE) {
      // Make sure to complete tokens and add the last command.
      if (\strlen($token)) {
        $tokens[] = $token;
      }
      if ($tokens) {
        $commands[] = $tokens;
        $tokens = [];
      }
    }
    else {
      throw new \Exception("Invalid closing tokenizer state: {$state}");
    }
    
    return $commands;
  }
  
}
