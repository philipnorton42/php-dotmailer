<?php

use Philipnorton42\DotMailer\DotMailer;

require_once __DIR__.'/../vendor/autoload.php';

try {
  $dotmailer = new DotMailer('', '');
} catch (\Exception $e) {
  die('DotMailer class not found' . PHP_EOL);
}

$addressBooks = $dotmailer->ListAddressBooks();
foreach ($addressBooks as $book) {
	print $book->ID  . ' => ' . $book->Name . PHP_EOL;
}
