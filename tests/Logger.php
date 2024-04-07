<?php
/**
 * @file
 * Simple logger.
 */

namespace SmpTest;

/**
 * Simple logger.
 */
class Logger {  
  public const LOG_LEVEL_DEBUG      = 100;
  public const LOG_LEVEL_INFO       = 200;
  public const LOG_LEVEL_NOTICE     = 250;
  public const LOG_LEVEL_WARNING    = 300;
  public const LOG_LEVEL_ERROR      = 400;
  public const LOG_LEVEL_CRITICAL   = 500;
  public const LOG_LEVEL_ALERT      = 550;
  public const LOG_LEVEL_EMERGENCY  = 600;
  
  protected $level_label = [
    Logger::LOG_LEVEL_DEBUG      => "DEBUG",
    Logger::LOG_LEVEL_INFO       => "INFO",
    Logger::LOG_LEVEL_NOTICE     => "NOTICE",
    Logger::LOG_LEVEL_WARNING    => "WARNING",
    Logger::LOG_LEVEL_ERROR      => "ERROR",
    Logger::LOG_LEVEL_CRITICAL   => "CRITICAL",
    Logger::LOG_LEVEL_ALERT      => "ALERT",
    Logger::LOG_LEVEL_EMERGENCY  => "EMERGENCY",
  ];
  
  protected $level = 100;
  protected $channel = 'Unknown';
  
  protected $fp = NULL;
  
  /**
   * Get a new logger.
   * 
   * Logger level is debug & stream is STDERR.
   * 
   * @param string $channel
   * @return \SmpTest\Logger
   */
  public static function getChannel($channel) {
    return new Logger($channel, Logger::LOG_LEVEL_DEBUG, STDERR);
  }
  
  function __construct($channel = 'Unknown', $level = Logger::LOG_LEVEL_INFO, $stream = NULL) {
    if ($stream && \is_resource($stream)) {
      $this->fp = $stream;
    }
    else if ($stream && \is_string($stream)) {
      $this->fp = @\fopen($stream, 'w');
    }
    else {
      $this->fp = STDOUT;
    }
    
    if (!$this->fp) {
      throw "Could not open log stream.";
    }
    
    $this->level = $level;
    $this->channel = $channel;
  }
  
  protected function writeToLogStream($msg) {
    \fwrite($this->fp, $msg);
  }
  
  protected function log($msg, $level = 0) {
    if ($this->level > $level) {
      return;
    }
    $level_label = 'UNKNOWN';
    if (isset($this->level_label[$level])) {
      $level_label = $this->level_label[$level];
    }
    $time = \time();
    $this->writeToLogStream("[{$time}] {$this->channel}.{$level_label}: $msg" . PHP_EOL);
  }
  
  public function close() {
    if ($this->fp) {
      \fflush($this->fp);
    }
    \fclose($this->fp);
  }
  
  public function debug($msg) {
    $this->log($msg, $this::LOG_LEVEL_DEBUG);
  }
  
  public function info($msg) {
    $this->log($msg, $this::LOG_LEVEL_INFO);
  }
  
  public function notice($msg) {
    $this->log($msg, $this::LOG_LEVEL_NOTICE);
  }
  
  public function warning($msg) {
    $this->log($msg, $this::LOG_LEVEL_WARNING);
  }
  
  public function error($msg) {
    $this->log($msg, $this::LOG_LEVEL_ERROR);
  }
  
  public function critical($msg) {
    $this->log($msg, $this::LOG_LEVEL_CRITICAL);
  }
  
  public function alert($msg) {
    $this->log($msg, $this::LOG_LEVEL_ALERT);
  }
  
  public function emergency($msg) {
    $this->log($msg, $this::LOG_LEVEL_EMERGENCY);
  }
}

class LoggerSelfTest {
  public function run() {
    $logger = new Logger("DebugClass", Logger::LOG_LEVEL_DEBUG);
    $logger->debug("debug");
    $logger->info("info");
    $logger->notice("notice");
    $logger->warning("warning");
    $logger->error("error");
    $logger->critical("critical");
    $logger->alert("alert");
    $logger->emergency("emergency");
    
    echo PHP_EOL;
    
    $logger = new Logger("InfoClass", Logger::LOG_LEVEL_INFO);
    $logger->debug("debug");
    $logger->info("info");
    $logger->notice("notice");
    $logger->warning("warning");
    $logger->error("error");
    $logger->critical("critical");
    $logger->alert("alert");
    $logger->emergency("emergency");
    
    echo PHP_EOL;
    
    $logger = new Logger("NoticeClass", Logger::LOG_LEVEL_NOTICE);
    $logger->debug("debug");
    $logger->info("info");
    $logger->notice("notice");
    $logger->warning("warning");
    $logger->error("error");
    $logger->critical("critical");
    $logger->alert("alert");
    $logger->emergency("emergency");
    
    echo PHP_EOL;
    
    $logger = new Logger("WarningClass", Logger::LOG_LEVEL_WARNING);
    $logger->debug("debug");
    $logger->info("info");
    $logger->notice("notice");
    $logger->warning("warning");
    $logger->error("error");
    $logger->critical("critical");
    $logger->alert("alert");
    $logger->emergency("emergency");
    
    echo PHP_EOL;
    
    $logger = new Logger("ErrorClass", Logger::LOG_LEVEL_ERROR);
    $logger->debug("debug");
    $logger->info("info");
    $logger->notice("notice");
    $logger->warning("warning");
    $logger->error("error");
    $logger->critical("critical");
    $logger->alert("alert");
    $logger->emergency("emergency");
  }
}
