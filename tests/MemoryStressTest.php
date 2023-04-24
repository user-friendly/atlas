<?php
/**
 * @file
 * Memory stress test.
 */

namespace Ants\tests;

use Ants\Main;
use Ants\Utility;
use Ants\Logger\LoggerFactory;
use Ants\Logger\LoggerTrait;

class MemoryStressTest implements Test {
    use LoggerTrait;
    
    // Multiply (1 << 20) by the number of MBs to get size in MBs.
    protected $size = (1 << 20) * 1;
    
    protected $end = 0;
    
    protected $buffer = '';
    
    public function __construct() {
        LoggerFactory::init();
        $this->logger = static::getLogger();
    }
    
    public function run() : int {
        $this->end = $this->size - 1;
        
        $this->size_text = Utility::formatSize($this->size);
        $this->logger->warning("Create a string using index offset, with length: {$this->size_text}");
        
        // $this->logger->warning("Hit enter to continue.");
        // readline();
        
        $this->buffer[$this->end] = chr(0x04);
        $this->buffer[1 << 16] = 'a';
        
        $this->report_memory();
        $this->report_buffer($this->buffer);
        
        $this->logger->warning("String manipulations.");
        $this->logger->warning("Hit enter to continue.");
        readline();
        
        $t_start = 1 << 16;
        $this->buffer = substr_replace($this->buffer, "bb", $t_start, 5);
        
        $this->report_memory();
        $this->report_buffer($this->buffer);
        
        $this->logger->warning("Cut it in half.");
        $this->logger->warning("Hit enter to continue.");
        readline();
        
        $this->buffer = \substr($this->buffer, (int) $this->size / 2);
        
        // $this->logger->warning("Pad the string.");
        // $this->logger->warning("Hit enter to continue.");
        // readline();
        
        // for ($i = 0; $i < $this->end; $i++) {
        //   $this->buffer[$i] = chr(mt_rand(0, 255));
        // }
        
        $this->report_memory();
        $this->report_buffer($this->buffer);
        
        $this->logger->warning("Test done. Hit enter to continue.");
        readline();
        
        return Main::EXIT_SUCCESS;
    }
    
    protected function report_memory() : void {
        $mem = memory_get_usage(TRUE);
        $mem_text = Utility::formatSize($mem);
        $this->logger->warning("Memory useage (real): {$mem_text} ({$mem})");
    }
    
    protected function report_buffer() : void {
        $str_size = strlen($this->buffer);
        $str_len = Utility::formatSize($str_size);
        $this->logger->warning("String length: {$str_len} ({$str_size})");
    }
}
