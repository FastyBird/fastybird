<?php declare(strict_types = 1);

/**
 * Storage.php
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

use FastyBird\Plugin\RedisDbCache\Clients;
use Nette\Caching;
use function array_merge;
use function array_unique;
use function assert;
use function is_string;

/**
 * Redis cache journal
 *
 * @package        FastyBird:RedisDbCachePlugin!
 * @subpackage     Caching
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Journal implements Caching\Storages\Journal
{

	public const NS_PREFIX = 'FB.Journal';

	public const KEY_TAG = 'tag';

	public const KEY_PRIORITY = 'priority';

	public const SUFFIX_TAGS = 'tags';

	public const SUFFIX_KEYS = 'keys';

	public function __construct(private readonly Clients\Client $client)
	{
	}

	/**
	 * Writes entry information into the journal.
	 *
	 * @param array<mixed> $dependencies
	 */
	public function write(string $key, array $dependencies): void
	{
		$this->client->multi();

		// Handle tags
		if ($dependencies[Caching\Cache::Tags] !== []) {
			$this->client->del($this->formatKey($key, self::SUFFIX_TAGS)); // Remove existing tags for this key

			foreach ((array) $dependencies[Caching\Cache::Tags] as $tag) {
				assert(is_string($tag));

				// Add key to the set for this tag
				$this->client->sAdd(
					$this->formatKey(self::KEY_TAG . ':' . $tag, self::SUFFIX_KEYS),
					[$key],
				);

				// Add tag to the set for this key
				$this->client->sAdd(
					$this->formatKey($key, self::SUFFIX_TAGS),
					[$tag],
				);
			}
		}

		// Handle priority
		if ($dependencies[Caching\Cache::Priority] !== []) {
			// Add key to the sorted set with priority as score
			$this->client->zAdd(
				$this->formatKey(self::KEY_PRIORITY),
				[$key => $dependencies[Caching\Cache::Priority]],
			);
		}

		$this->client->exec();
	}

	/**
	 * Cleans entries from journal
	 * Return array of removed items or NULL when performing a full cleanup
	 *
	 * @param array<mixed> $conditions
	 *
	 * @return array<mixed>|null
	 */
	public function clean(array $conditions): array|null
	{
		if (isset($conditions[Caching\Cache::All])) {
			// Remove all keys under the namespace
			$allKeys = $this->client->keys($this->formatKey('*', '*'));

			if ($allKeys !== []) {
				$this->client->del($allKeys);
			}

			return null;
		}

		$keysToRemove = [];

		// Clean by tags
		if (isset($conditions[Caching\Cache::Tags]) && $conditions[Caching\Cache::Tags] !== []) {
			$tags = (array) $conditions[Caching\Cache::Tags];

			foreach ($tags as $tag) {
				$taggedKeys = $this->client->sMembers($this->formatKey(self::KEY_TAG . ':' . $tag, self::SUFFIX_KEYS));

				$keysToRemove = array_merge($keysToRemove, $taggedKeys);
			}
		}

		// Clean by priority
		if (isset($conditions[Caching\Cache::Priority]) && $conditions[Caching\Cache::Priority] !== []) {
			$priorityKeys = $this->client->zRangeByScore(
				$this->formatKey(self::KEY_PRIORITY),
				0,
				$conditions[Caching\Cache::Priority],
			);

			$keysToRemove = array_merge($keysToRemove, $priorityKeys);
		}

		$keysToRemove = array_unique($keysToRemove);

		if ($keysToRemove === []) {
			return [];
		}

		$keyTagsMap = [];

		foreach ($keysToRemove as $key) {
			$keyTagsMap[$key] = $this->client->sMembers($this->formatKey($key, self::SUFFIX_TAGS));
		}

		$this->client->multi();

		foreach ($keysToRemove as $key) {
			foreach ($keyTagsMap[$key] as $tag) {
				$this->client->sRem($this->formatKey(self::KEY_TAG . ':' . $tag, self::SUFFIX_KEYS), $key);
			}

			$this->client->del($this->formatKey($key, self::SUFFIX_TAGS)); // Remove all tags for this key
			$this->client->zRem($this->formatKey(self::KEY_PRIORITY), $key); // Remove key from priority sorted set
		}

		$this->client->exec();

		return $keysToRemove;
	}

	public function clearKey(string $key): void
	{
		// Retrieve all tags associated with this key
		$tags = $this->client->sMembers($this->formatKey($key, self::SUFFIX_TAGS));

		$this->client->multi();

		// Remove this key from all tag sets
		foreach ($tags as $tag) {
			$this->client->sRem($this->formatKey(self::KEY_TAG . ':' . $tag, self::SUFFIX_KEYS), $key);
		}

		// Remove the key's tags set
		$this->client->del($this->formatKey($key, self::SUFFIX_TAGS));

		// Remove the key from the priority sorted set, if it exists
		$this->client->zRem($this->formatKey(self::KEY_PRIORITY), $key);

		$this->client->exec();
	}

	private function formatKey(string $key, string|null $suffix = null): string
	{
		return self::NS_PREFIX . ':' . $key . ($suffix !== null ? ':' . $suffix : '');
	}

}
