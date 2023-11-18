<?php declare(strict_types = 1);

/**
 * Repository.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Models
 * @since          1.0.0
 *
 * @date           13.11.23
 */

namespace FastyBird\Module\Devices\Models\Configuration;

use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\Models;
use function array_key_exists;
use function is_array;

/**
 * Configuration repository
 *
 * @template T of MetadataDocuments\Document
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
abstract class Repository
{

	/** @var array<string, T|array<T>|null>  */
	private array $cacheData = [];

	public function __construct(
		protected readonly Models\Configuration\Builder $builder,
	)
	{
		$this->builder->on('build', function (): void {
			$this->cacheData = [];
		});
	}

	/**
	 * @return T|array<T>|false|null
	 */
	protected function loadCache(string $key): MetadataDocuments\Document|array|false|null
	{
		if (array_key_exists($key, $this->cacheData)) {
			return $this->cacheData[$key];
		}

		return false;
	}

	/**
	 * @return T|false|null
	 *
	 * @throws Exceptions\InvalidState
	 */
	protected function loadCacheOne(string $key): MetadataDocuments\Document|false|null
	{
		$data = $this->loadCache($key . '_one');

		if ($data === false) {
			return false;
		}

		if ($data === null || $data instanceof MetadataDocuments\Document) {
			return $data;
		}

		throw new Exceptions\InvalidState('Failed loading data from cache');
	}

	/**
	 * @return array<T>|false
	 *
	 * @throws Exceptions\InvalidState
	 */
	protected function loadCacheAll(string $key): array|false
	{
		$data = $this->loadCache($key . '_all');

		if ($data === false) {
			return false;
		}

		if (is_array($data)) {
			return $data;
		}

		throw new Exceptions\InvalidState('Failed loading data from cache');
	}

	/**
	 * @param T|array<T>|null $data
	 */
	protected function writeCache(string $key, MetadataDocuments\Document|array|null $data): void
	{
		$this->cacheData[$key] = $data;
	}

	/**
	 * @param T|null $data
	 */
	protected function writeCacheOne(string $key, MetadataDocuments\Document|null $data): void
	{
		$this->writeCache($key . '_one', $data);
	}

	/**
	 * @param array<T> $data
	 */
	protected function writeCacheAll(string $key, array $data): void
	{
		$this->writeCache($key . '_all', $data);
	}

}
