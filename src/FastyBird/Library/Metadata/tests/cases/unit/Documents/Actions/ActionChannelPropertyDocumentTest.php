<?php declare(strict_types = 1);

namespace FastyBird\Library\Metadata\Tests\Cases\Unit\Documents\Actions;

use FastyBird\Library\Metadata\Documents;
use FastyBird\Library\Metadata\Exceptions;
use FastyBird\Library\Metadata\Tests\Cases\Unit\BaseTestCase;
use Nette;
use function file_get_contents;

final class ActionChannelPropertyDocumentTest extends BaseTestCase
{

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\MalformedInput
	 * @throws Nette\DI\MissingServiceException
	 *
	 * @dataProvider channelProperty
	 */
	public function testCreateDocument(string $data, string $class): void
	{
		$factory = $this->container->getByType(Documents\DocumentFactory::class);

		$document = $factory->create(Documents\Actions\ActionChannelProperty::class, $data);

		self::assertTrue($document instanceof $class);
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\MalformedInput
	 * @throws Nette\DI\MissingServiceException
	 *
	 * @dataProvider channelPropertyInvalid
	 */
	public function testCreateDocumentInvalid(string $data): void
	{
		$factory = $this->container->getByType(Documents\DocumentFactory::class);

		$this->expectException(Exceptions\InvalidArgument::class);

		$factory->create(Documents\Actions\ActionChannelProperty::class, $data);
	}

	/**
	 * @return array<string, array<string|bool>>
	 */
	public static function channelProperty(): array
	{
		return [
			'get' => [
				file_get_contents(__DIR__ . '/../../../../fixtures/Documents/Actions/channel.property.get.json'),
				Documents\Actions\ActionChannelProperty::class,
			],
			'set' => [
				file_get_contents(__DIR__ . '/../../../../fixtures/Documents/Actions/channel.property.set.json'),
				Documents\Actions\ActionChannelProperty::class,
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
				file_get_contents(__DIR__ . '/../../../../fixtures/Documents/Actions/channel.property.missing.json'),
			],
		];
	}

}
