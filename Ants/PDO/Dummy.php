<?php
/**
 * @file
 * Dummy PDO class file.
 */

namespace Ants\PDO;

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
  
  public static function generateDummyPayload_String($size = 256, int $char = NULL) : string {
    $payload = static::generateDummyPayload_Array($size, $char);
    return \call_user_func_array('pack', \array_merge(['c*'], $payload));
  }
  
  public static function generateDummyPayload_Array($size = 256, int $char = NULL) : array {
    $payload = [];
    for ($i = 0; $i < $size; $i++) {
      if ($char !== NULL) {
        $payload[] = \chr($char);
      }
      else {
        $payload[] = \chr(\mt_rand(0, 255));
      }
    }
    return $payload;
  }
}





















