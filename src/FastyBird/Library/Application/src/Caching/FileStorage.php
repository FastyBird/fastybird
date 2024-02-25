<?php declare(strict_types = 1);

/**
 * FileStorage.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Application!
 * @subpackage     Caching
 * @since          1.0.0
 *
 * @date           08.03.20
 */

namespace FastyBird\Library\Application\Caching;

use Nette\Caching\Storages;
use function dirname;
use function is_dir;
use function is_file;
use function mkdir;

class FileStorage extends Storages\FileStorage
{

	public function lock(string $key): void
	{
		$cacheFile = $this->getCacheFile($key);

		if (!is_dir($dir = dirname($cacheFile))) {
			@mkdir($dir); // @ - directory may already exist
		}

		if (!is_file($cacheFile)) {
			return;
		}

		parent::lock($key);
	}

}
