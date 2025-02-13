<?php
/**
 * @file
 *
 * GameStats class file.
 */

namespace Smp;

use Smp\Logger\LoggerFactory;
use Smp\Logger\LoggerTrait;

/**
 * Gather and display game statistics.
 */
class GameStats {
  use LoggerTrait;
  
  /**
   *
   * @var integer
   */
  protected $ticksMeanCount = 0;
  
  protected $timer = 0;
  
  /**
   * Ticks per second, actual.
   */
  public $ticksPerSecond = 0;
  
  /**
   * Mean time per tick.
   * 
   * @var integer
   */
  public $ticksTimeMean = 0;
  
  /**
   * Tick profile flush interval.
   * 
   * Displays profile data and resets it.
   * In seconds.
   * 
   * @var integer
   */
  public $ticksMeanFlush = 60;
  
  public function __construct(int $ticksPerSecond = 60) {
    LoggerFactory::init();
    $this->logger = static::getLogger();
    
    $this->ticksTimeMean = 0;
    $this->ticksMeanFlush *= $ticksPerSecond;
    $this->ticksMeanCount = 0;
    
    $this->timer = Utility::getTime();
  }
  
  public function profileTick(int $time) {
    $this->ticksTimeMean += $time;
    $this->ticksMeanCount += 1;
    if ($this->ticksMeanCount >= $this->ticksMeanFlush) {
      $this->ticksTimeMean = (int) ($this->ticksTimeMean / $this->ticksMeanCount);
      
      $dbg_ticks_time_mean = Utility::formatTimeHR($this->ticksTimeMean);
      $this->logger->debug("Mean tick time: $dbg_ticks_time_mean (total ns: $this->ticksTimeMean)");
      
      $dbg_ticks_per_second = $this->ticksMeanCount / (Utility::getTime() - $this->timer);
      $this->logger->debug("Mean tps: $dbg_ticks_per_second");
      
      $this->ticksTimeMean = 0;
      $this->ticksMeanCount = 0;
      
      $this->timer = Utility::getTime();
    }
  }
}
