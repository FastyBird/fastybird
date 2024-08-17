<?php declare(strict_types = 1);

namespace FastyBird\Plugin\RedisDbCache\Tests\Cases\Unit\Caching;

use FastyBird\Plugin\RedisDbCache\Caching;
use FastyBird\Plugin\RedisDbCache\Clients;
use FastyBird\Plugin\RedisDbCache\Exceptions;
use FastyBird\Plugin\RedisDbCache\Tests;

final class StorageTest extends Tests\Cases\Unit\BaseTestCase
{

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function testCache(): void
	{
		$storage = (object) ['unserialized' => 'unserialized'];

		$client = $this->createMock(Clients\Client::class);
		$client
			->method('set')
			->willReturnCallback(static function (string $key, string $data) use ($storage): bool {
				$storage->{$key} = $data;

				return true;
			});
		$client
			->method('del')
			->willReturnCallback(static function (string $key) use ($storage): bool {
				$storage->{$key} = null;

				return true;
			});
		$client
			->method('get')
			->willReturnCallback(static fn (string $key): mixed => $storage->{$key} ?? null);
		$client
			->method('mGet')
			->willReturnCallback(static function (array $keys) use ($storage): array {
				$result = [];

				foreach ($keys as $index => $key) {
					$result[$index] = $storage->{$key} ?? null;
				}

				return $result;
			});

		$redis = new Caching\Storage($client);

		$redis->write('foo', 'bar', []);
		self::assertSame('bar', $redis->read('foo'));

		$redis->write('foo', 'bat', []);
		self::assertSame('bat', $redis->read('foo'));

		$redis->remove('foo');
		self::assertNull($redis->read('foo'));
		self::assertNull($redis->read('unserialized'));

		$redis->write('false', false, []);
		self::assertFalse($redis->read('false'));

		$redis->write('text', 'abcd', []);
		$redis->write('isValid', true, []);
		$redis->write('data', null, []);
		$redis->write('name', 'redis', []);

		$data = $redis->multiRead(['text', 'isValid', 'data']);

		self::assertCount(3, $data);
		self::assertSame('abcd', $data['text']);
		self::assertTrue($data['isValid']);
		self::assertNull($data['data']);
		self::assertArrayNotHasKey('name', $data);
	}

}
