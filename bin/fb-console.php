<?php declare(strict_types = 1);

use const DIRECTORY_SEPARATOR as DS;

$boostrapFile = false;

$path = __DIR__;

for ($i = 0;$i < 10;$i++) {
	$path .= DS . '..';

	$srcPath = realpath($path . DS . 'src');

	if ($srcPath !== false) {
		$boostrapFile = realpath($path) . DS . 'src' . DS . 'FastyBird' . DS . 'Library' . DS . 'Application' . DS . 'bin' . DS . 'fb-console.php';

		break;
	}
}

if ($boostrapFile === false || !file_exists($boostrapFile)) {
	echo "Application file not found." . PHP_EOL;

	exit(1);
}

include($boostrapFile);