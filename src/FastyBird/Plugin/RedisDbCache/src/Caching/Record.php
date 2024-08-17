<?php declare(strict_types = 1);

/**
 * Record.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RedisDbCachePlugin!
 * @subpackage     Caching
 * @since          1.0.0
 *
 * @date           15.08.24
 */

namespace FastyBird\Plugin\RedisDbCache\Caching;

use Nette;

/**
 * Cache record item
 *
 * @package        FastyBird:RedisDbCachePlugin!
 * @subpackage     Caching
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Record
{

	use Nette\SmartObject;

	/**
	 * @param array<mixed> $meta
	 */
	public function __construct(
		private readonly string $key,
		private readonly array $meta,
		private readonly string $data,
	)
	{
	}

	public function getKey(): string
	{
		return $this->key;
	}

	/**
	 * @return array<mixed>
	 */
	public function getMeta(): array
	{
		return $this->meta;
	}

	public function getData(): string
	{
		return $this->data;
	}

}
