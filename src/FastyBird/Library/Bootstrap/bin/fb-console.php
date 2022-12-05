<?php declare(strict_types = 1);

use FastyBird\Library\Bootstrap\Boot;
use Symfony\Component\Console;
use const DIRECTORY_SEPARATOR as DS;

$autoload = null;

$autoloadFiles = [
	__DIR__ . DS . '..' . DS . 'vendor' . DS . 'autoload.php',
	__DIR__ . DS . '..' . DS . '..' . DS . '..' . DS . 'autoload.php',
	__DIR__ . DS . '..' . DS . '..' . DS . '..' . DS . '..' . DS . '..' . DS . 'vendor' . DS . 'autoload.php',
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

$boostrap = Boot\Bootstrap::boot();

// Clear cache before startup
if (defined('FB_TEMP_DIR') && is_dir(FB_TEMP_DIR)) {
	$di = new RecursiveDirectoryIterator(FB_TEMP_DIR, FilesystemIterator::SKIP_DOTS);
	$ri = new RecursiveIteratorIterator($di, RecursiveIteratorIterator::CHILD_FIRST);

	foreach ($ri as $file) {
		assert($file instanceof SplFileInfo);
		if (!$file->isDir() && basename(strval($file)) === '.gitignore') {
			continue;
		}

		$file->isDir() ? rmdir(strval($file)) : unlink(strval($file));
	}
}

$container = $boostrap->createContainer();

$console = $container->getByType(Console\Application::class);

// Clear cache
exit($console->run());
