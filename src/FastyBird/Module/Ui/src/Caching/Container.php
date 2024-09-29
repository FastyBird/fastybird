<?php declare(strict_types = 1);

/**
 * Container.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:UiModule!
 * @subpackage     Caching
 * @since          1.0.0
 *
 * @date           18.03.20
 */

namespace FastyBird\Module\Ui\Caching;

use Nette\Caching;

/**
 * Module cache container
 *
 * @package        FastyBird:UiModule!
 * @subpackage     Caching
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
readonly class Container
{

	public function __construct(
		private Caching\Cache $configurationBuilderCache,
		private Caching\Cache $configurationRepositoryCache,
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

}
