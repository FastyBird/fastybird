<?php declare(strict_types = 1);

namespace FastyBird\Library\Metadata\Tests\Cases\Unit\Entities\Actions;

use FastyBird\Library\Metadata\Entities;
use FastyBird\Library\Metadata\Exceptions;
use FastyBird\Library\Metadata\Tests\Cases\Unit\BaseTestCase;
use Nette;
use function file_get_contents;

final class ActionChannelPropertyEntityTest extends BaseTestCase
{

	/**
	 * @throws Exceptions\FileNotFound
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidData
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Logic
	 * @throws Exceptions\MalformedInput
	 * @throws Nette\DI\MissingServiceException
	 *
	 * @dataProvider channelProperty
	 */
	public function XtestCreateEntity(string $data, string $class): void
	{
		$factory = $this->container->getByType(Entities\Actions\ActionChannelPropertyEntityFactory::class);

		$entity = $factory->create($data);

		self::assertTrue($entity instanceof $class);
	}

	/**
	 * @throws Exceptions\FileNotFound
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidData
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Logic
	 * @throws Exceptions\MalformedInput
	 * @throws Nette\DI\MissingServiceException
	 *
	 * @dataProvider channelPropertyInvalid
	 */
	public function XtestCreateEntityInvalid(string $data): void
	{
		$factory = $this->container->getByType(Entities\Actions\ActionChannelPropertyEntityFactory::class);

		$this->expectException(Exceptions\InvalidData::class);

		$factory->create($data);
	}

	/**
	 * @return array<string, array<string|bool>>
	 */
	public static function channelProperty(): array
	{
		return [
			'get' => [
				file_get_contents(__DIR__ . '/../../../../fixtures/Entities/Actions/channel.property.get.json'),
				Entities\Actions\ActionChannelProperty::class,
			],
			'set' => [
				file_get_contents(__DIR__ . '/../../../../fixtures/Entities/Actions/channel.property.set.json'),
				Entities\Actions\ActionChannelProperty::class,
			],
		];
	}

	/**
	 * @return array<string, array<string|bool>>
	 */
	public static function channelPropertyInvalid(): array
	{
		return [
			'missing' => [
				file_get_contents(__DIR__ . '/../../../../fixtures/Entities/Actions/channel.property.missing.json'),
			],
		];
	}

}
