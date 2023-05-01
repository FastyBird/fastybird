<?php
declare(strict_types = 1);

$config = [];

if (PHP_VERSION_ID >= 8_02_00) {
	// Change of signature in PHP 8.2
	$config['parameters']['ignoreErrors'][] = [
		'message' => '~Method FastyBird\\\\Library\\\\Bootstrap\\\\Helpers\\\\Database::reconnect\(\) has Doctrine\\\\DBAL\\\\Exception in PHPDoc @throws tag but it\'s not thrown.~',
		'path' => './src/FastyBird/Library/Bootstrap/src/Helpers/Database.php',
		'count' => 1,
	];
}

return $config;
