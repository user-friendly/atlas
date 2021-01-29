<?php
/**
 * @file
 * Program entry point.
 */

namespace Parallel;

class Main {
  const EXIT_SUCCESS = 0;
  const EXIT_FAILURE = 1;
  
  /**
   * @var \Parallel\Process
   */
  protected $mainProcess;
  
  /**
   * An array of sub processes.
   * 
   * @see \Parallel\Process
   * 
   * @var array
   */
  protected $subProcess = [];
  
  public function run() : int {
    if (DEBUG) {
      print "Main::run() entered.\n";
    }
    $this->mainProcess = new Process();
    
    $this->subProcess[] = $this->mainProcess->fork();
    
    $this->mainProcess->execute(1);
    
    if (DEBUG) {
      print "Main::run() done.\n";
    }
    return self::EXIT_SUCCESS;
  }
}



























