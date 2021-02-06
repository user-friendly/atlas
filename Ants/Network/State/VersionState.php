<?php
/**
 * @file
 * VersionState class.
 */

namespace Ants\Network\State;

use Ants\Network\Connection;
use Ants\Main;

/**
 * VersionState class.
 */
class VersionState implements StateInterface {
  
  protected $major;
  protected $minor;
  protected $patch;

  /**
   * {@inheritDoc}
   */
  public function __construct(Connection $conn) {
    list($this->major, $this->minor, $this->patch) = $this->unpackVersion(Main::VERSION);
  }

  /**
   * {@inheritDoc}
   * 
   * @see \Ants\Network\State\StateInterface::handle()
   */
  public function handle(Connection $conn) {
    
  }
  
  protected function unpackVersion(string $version) {
    list($major, $minor, $patch) = \explode(".", $version);
    return [(int) $major, (int) $minor, (int) $patch];
  }
}
