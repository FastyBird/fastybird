<?php

declare(strict_types=1);

use Dotenv\Dotenv;
use FastyBird\Library\Bootstrap\Boot;
use FastyBird\Plugin\WebServer\Application as WebServerApplication;

if (isset($_ENV['FB_APP_DIR'])) {
	$vendorDir = realpath($_ENV['FB_APP_DIR'] . DIRECTORY_SEPARATOR . 'vendor');
	$envDir = realpath($_ENV['FB_APP_DIR'] . DIRECTORY_SEPARATOR . 'env');

} else {
	$vendorDir = realpath(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'vendor');
	$envDir = realpath(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'env');

	define('FB_APP_DIR', realpath(__DIR__ . DIRECTORY_SEPARATOR . '..'));
	define('FB_PUBLIC_DIR', realpath(__DIR__));
}

define('FB_CONFIG_DIR', realpath(FB_APP_DIR . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'config'));

$autoload = $vendorDir . DIRECTORY_SEPARATOR . 'autoload.php';

if (file_exists($autoload)) {
	require $autoload;

	if ($envDir !== false) {
		try {
			$dotEnv = Dotenv::createImmutable($envDir);
			$dotEnv->load();

		} catch (Throwable $ex) {
			// Env files could not be loaded
		}
	}

	$configurator = Boot\Bootstrap::boot();

	$configurator
		->createContainer()
		->getByType(WebServerApplication\Application::class)
		->run();

} else {
	echo 'Composer autoload not found!';
}
