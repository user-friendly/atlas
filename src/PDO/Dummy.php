<?php
/**
 * @file
 * Dummy PDO class file.
 */

namespace Parallel\PDO;

/**
 * Used demonstration purposes.
 */
class Dummy
{
  protected $id;
  
  protected $data = [];

  public function __construct() {
    $this->id = \mt_rand(0, 9999999);
    
    $this->data['text'] = 'TODO Generate some \n\n random text';
    $this->data['number'] = \mt_rand(-999999, 999999);
  }
  
  public function getId() {
    return $this->id;
  }
  
  public function getData() {
    return $this->preGet($this->data);
  }
  
  protected function preGet(array $data) : array {
    $data['number'] >>= 3;
    $data['numner'] <<= 1;
    $data['obj'] = new \stdClass();
    $data['obj']->id = $this->id;
    $data['obj']->test = 'Some nonesense!\n\nThis should throw off the parser.';
    return $data;
  }
}

