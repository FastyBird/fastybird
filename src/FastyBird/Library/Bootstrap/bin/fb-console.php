<?php
/**
 * console.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Bootstrap!
 * @subpackage     bin
 * @since          0.1.0
 *
 * @date           08.03.20
 */

declare(strict_types = 1);

use Dotenv\Dotenv;
use FastyBird\Bootstrap\Boot;
use Symfony\Component\Console;
use const DIRECTORY_SEPARATOR as DS;

$autoload = null;

$autoloadFiles = [
	__DIR__ . DS . DS . 'vendor' . DS . 'autoload.php',
	__DIR__ . DS . DS . 'autoload.php',
];

foreach ($autoloadFiles as $autoloadFile) {
	if (file_exists($autoloadFile)) {
		$autoload = realpath($autoloadFile);
		break;
	}
}

if ($autoload === null) {
	echo "Autoload file not found; try 'composer dump-autoload' first." . PHP_EOL;

	exit(1);
}

require $autoload;

if (isset($_ENV['FB_APP_DIR'])) {
	$envDirs = [$_ENV['FB_APP_DIR'] . DS . 'env'];

} else {
	$envDirs = [
		__DIR__ . DS . DS . 'env',
		__DIR__ . DS . DS . 'env',
	];
}

foreach ($envDirs as $envDir) {
	if (is_dir($envDir) && realpath($envDir) !== false) {
		$dotEnv = Dotenv::createImmutable(realpath($envDir));
		$dotEnv->safeLoad();
		break;
	}
}

$boostrap = Boot\Bootstrap::boot();

// Clear cache before startup
if (defined('FB_TEMP_DIR') && is_dir(FB_TEMP_DIR)) {
	$di = new RecursiveDirectoryIterator(FB_TEMP_DIR, FilesystemIterator::SKIP_DOTS);
	$ri = new RecursiveIteratorIterator($di, RecursiveIteratorIterator::CHILD_FIRST);

	foreach ($ri as $file) {
		if (!$file->isDir() && basename((string) $file) === '.gitignore') {
			continue;
		}

		$file->isDir() ? rmdir((string) $file) : unlink((string) $file);
	}
}

$container = $boostrap->createContainer();

/** @var Console\Application $console */
$console = $container->getByType(Console\Application::class);

// Clear cache
exit($console->run());
