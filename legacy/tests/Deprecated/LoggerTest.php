<?php
/**
 * @file
 * LoggerTest file.
 */

$this->logger->emergency("this is a test EMERGENCY", ["test"]);
$this->logger->critical("this is a test CRITICAL");
$this->logger->alert("this is a test ALERT");
$this->logger->error("this is a test ERROR");
$this->logger->warning("this is a test WARNING");
$this->logger->notice("this is a test NOTICE");
$this->logger->info("this is a test INFO");
$this->logger->debug("this is a test DEBUG");
