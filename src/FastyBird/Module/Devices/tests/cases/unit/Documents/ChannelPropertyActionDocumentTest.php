<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Tests\Cases\Unit\Documents;

use Error;
use FastyBird\Library\Application\Exceptions as ApplicationExceptions;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Module\Devices\Documents;
use FastyBird\Module\Devices\Tests\Cases\Unit\BaseTestCase;
use Nette;
use function file_get_contents;

final class ChannelPropertyActionDocumentTest extends BaseTestCase
{

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws Error
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\Mapping
	 * @throws MetadataExceptions\MalformedInput
	 * @throws Nette\DI\MissingServiceException
	 *
	 * @dataProvider channelProperty
	 */
	public function testCreateDocument(string $data, string $class): void
	{
		$factory = $this->getContainer()->getByType(MetadataDocuments\DocumentFactory::class);

		$document = $factory->create(Documents\Actions\Properties\Channel::class, $data);

		self::assertTrue($document instanceof $class);
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws Error
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\Mapping
	 * @throws MetadataExceptions\MalformedInput
	 * @throws Nette\DI\MissingServiceException
	 *
	 * @dataProvider channelPropertyInvalid
	 */
	public function testCreateDocumentInvalid(string $data): void
	{
		$factory = $this->getContainer()->getByType(MetadataDocuments\DocumentFactory::class);

		$this->expectException(MetadataExceptions\InvalidArgument::class);

		$factory->create(Documents\Actions\Properties\Channel::class, $data);
	}

	/**
	 * @return array<string, array<string|bool>>
	 */
	public static function channelProperty(): array
	{
		return [
			'get' => [
				file_get_contents(__DIR__ . '/../../../fixtures/Documents/channel.property.action.get.json'),
				Documents\Actions\Properties\Channel::class,
			],
			'set' => [
				file_get_contents(__DIR__ . '/../../../fixtures/Documents/channel.property.action.set.json'),
				Documents\Actions\Properties\Channel::class,
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
				file_get_contents(
					__DIR__ . '/../../../fixtures/Documents/channel.property.action.missing.json',
				),
			],
		];
	}

}
