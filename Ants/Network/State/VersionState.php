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
 * 
 * After the handshake, the version state will determine whether the two
 * parties' APIs are compatible and can communicate. As with the HelloState,
 * this message format is not TLV but fixed in size.
 */
class VersionState extends BaseState {
  
  protected $major;
  protected $minor;
  protected $patch;
  
  /**
   * This is the version packed as a string.
   * 
   * The three components will be packed as unsigned shorts. This means the max
   * int value for each component that can be transmitted is 2^16 - 1, which is
   * plenty. Byte order is big-endian.
   * 
   * @var string
   */
  protected $packedVersion;
  
  /**
   * {@inheritDoc}
   */
  public function __construct(Connection $conn) {
    parent::__construct($conn);
    
    list($this->major, $this->minor, $this->patch) = $this->unpackVersion(Main::VERSION);
    
    $this->packedVersion = \pack('n3', $this->major, $this->minor, $this->patch);
    // Immediately send the packed version to the other party.
    $this->connection->append($this->packedVersion);
  }

  /**
   * {@inheritDoc}
   * 
   * @see \Ants\Network\State\StateInterface::handle()
   */
  public function handle(Connection $conn) : void {
    // The lenght of the packed version should always be the same, no matter
    // what values are the components.
    $binary = $conn->consume(\strlen($this->packedVersion));
    if (!$binary) {
      return;
    }
    $unpacked = \unpack('n3', $binary);
    if ($unpacked && $this->checkVersions($unpacked)) {
      $conn->setState(new ReceiveState($conn));
    }
    else {
      $conn->setState(new HaltState($conn));
    }
  }
  
  protected function checkVersions(array $unpacked) : bool {
    // The unpacked array starts at index 1.
    list($remote_major, $remote_minor, $remote_patch) = \array_values($unpacked);
    $remote_version = "{$remote_major}.{$remote_minor}.{$remote_patch}";
    $local_version = Main::VERSION;
    $this->logger->debug("Remote version is {$remote_version}, local version is {$local_version}.");
    // NOTE Makes sense only the major versions to match. Future application
    //      changes might put more restrictions on the other components. 
    if ($remote_major != $this->major) {
      $this->logger->warning("Major versions mismatch. Local is {$this->major}, remote is {$remote_major}");
      return FALSE;
    }
    $this->logger->info("Remote ({$remote_major}) and local ({$this->major}) major versions are compatible.");
    return TRUE;
  }
  
  protected function unpackVersion(string $version) {
    list($major, $minor, $patch) = \explode(".", $version);
    return [(int) $major, (int) $minor, (int) $patch];
  }
}
