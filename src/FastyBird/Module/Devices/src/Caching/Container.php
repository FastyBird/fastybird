<?php declare(strict_types = 1);

/**
 * Container.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Caching
 * @since          1.0.0
 *
 * @date           12.09.24
 */

namespace FastyBird\Module\Devices\Caching;

use Nette\Caching;

/**
 * Module cache container
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Caching
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
readonly class Container
{

	public function __construct(
		private Caching\Cache $configurationBuilderCache,
		private Caching\Cache $configurationRepositoryCache,
		private Caching\Cache $stateCache,
		private Caching\Cache $stateStorageCache,
	)
	{
	}

	public function getConfigurationBuilderCache(): Caching\Cache
	{
		return $this->configurationBuilderCache;
	}

	public function getConfigurationRepositoryCache(): Caching\Cache
	{
		return $this->configurationRepositoryCache;
	}

	public function getStateCache(): Caching\Cache
	{
		return $this->stateCache;
	}

	public function getStateStorageCache(): Caching\Cache
	{
		return $this->stateStorageCache;
	}

}
