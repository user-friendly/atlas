<?php

define("EOT", 0x0004);

$fp = fopen('obj.bin', 'wb');

if (!$fp) {
  throw new \RuntimeException("Faild to open test binary file.");
}

$data = NULL;

// $data = chr(0x1);
// $data .= chr(0x2);
// $data .= chr(0xA);

$data = pack("s*", EOT, EOT);

$bytes = fwrite($fp, $data);


print "$bytes bytes written to binary file\n";

fclose($fp);
