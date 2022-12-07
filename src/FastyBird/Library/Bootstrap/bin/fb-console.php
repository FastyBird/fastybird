<?php declare(strict_types = 1);

use FastyBird\Library\Bootstrap\Boot;
use Symfony\Component\Console;
use const DIRECTORY_SEPARATOR as DS;

$autoloadFile = false;

$path = __DIR__;

for ($i = 0;$i < 10;$i++) {
	$path .= DS . '..';

	$vendorPath = realpath($path . DS . 'vendor');

	if ($vendorPath !== false) {
		$autoloadFile = realpath($path) . DS . 'autoload.php';

		break;
	}
}

if ($autoloadFile === false || !file_exists($autoloadFile)) {
	echo "Autoload file not found; try 'composer dump-autoload' first." . PHP_EOL;

	exit(1);
}

require $autoloadFile;

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
