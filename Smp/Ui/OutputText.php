<?php
/**
 * @file
 *
 * OutputText class file.
 * 
 * TODO This should be the UI adapter between the game
 *      and the user (game -> user).
 */

namespace Smp\Ui;

use Smp\Logger\LoggerTrait;
use Smp\Logger\LoggerFactory;

/**
 * Output handler for text UI.
 */
class OutputText {
  use LoggerTrait;
  
  const NEW_LINE = "\n";
  
  /**
   * Maximum buffer size per user. 
   * 
   * @var integer
   */
  const BUFFER_MAX = 1024;
  
  protected $buffers = [];
  
  public function __construct() {
    LoggerFactory::init();
    $this->logger = static::getLogger();
  }
  
  /**
   * Print text for user.
   * 
   * @param int           $uid    User id.
   * @param string|NULL   $text   Optional. If omitted, will print the prompt.
   * @return \Smp\Ui\OutputText
   */
  public function print($uid, $text = NULL) {
    if (!isset($this->buffers[$uid])) {
      $this->buffers[$uid] = '';
    }
    // FIXME You think you're clever, but you're not.
    $this->buffers[$uid] = \substr($this->buffers[$uid] .  $text, 0, $this::BUFFER_MAX);
    return $this;
  }
  
  /**
   * Print text for user.
   * 
   * Add a new line character at the end.
   * 
   * @param int           $uid    User id.
   * @param string|NULL   $text   Optional. If omitted, will print the prompt.
   * @return \Smp\Ui\OutputText
   */
  public function println($uid, $text = NULL) {
    $this->print($uid, $text . $this::NEW_LINE);
  }
  
  /**
   * Reads and flush output per user.
   * 
   * Maximum length of each user buffer is Smp\Ui\OutputText::BUFFER_MAX.
   */
  public function processOutput() {
    foreach ($this->buffers as $uid => $text) {
      yield $uid => $text;
    }
    $this->buffers = [];
  }
  
  /**
   * Reads and flush output per user.
   *
   * Maximum length of each user buffer is Smp\Ui\OutputText::BUFFER_MAX.
   */
  public function peekOutput() {
    foreach ($this->buffers as $uid => $text) {
      yield $uid => $text;
    }
  }
}
