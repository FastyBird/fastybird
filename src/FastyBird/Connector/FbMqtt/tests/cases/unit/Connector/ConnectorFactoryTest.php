<?php declare(strict_types = 1);

namespace FastyBird\Connector\FbMqtt\Tests\Cases\Unit\Connector;

use Exception;
use FastyBird\Connector\FbMqtt\Connector;
use FastyBird\Connector\FbMqtt\Exceptions;
use FastyBird\Connector\FbMqtt\Tests\Cases\Unit\DbTestCase;
use FastyBird\Library\Metadata\Entities as MetadataEntities;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Module\Devices\DataStorage as DevicesDataStorage;
use FastyBird\Module\Devices\Models as DevicesModels;
use League\Flysystem;
use Nette;
use Ramsey\Uuid;
use RuntimeException;
use function assert;

final class ConnectorFactoryTest extends DbTestCase
{

	/**
	 * @throws Exception
	 * @throws Exceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws Nette\Utils\JsonException
	 * @throws Flysystem\FilesystemException
	 * @throws RuntimeException
	 */
	public function setUp(): void
	{
		parent::setUp();

		$writer = $this->getContainer()->getByType(DevicesDataStorage\Writer::class);
		$reader = $this->getContainer()->getByType(DevicesDataStorage\Reader::class);

		$writer->write();
		$reader->read();
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws MetadataExceptions\FileNotFound
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\Logic
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 */
	public function testCreateConnector(): void
	{
		$connectorsRepository = $this->getContainer()->getByType(
			DevicesModels\DataStorage\ConnectorsRepository::class,
		);

		$factory = $this->getContainer()->getByType(Connector\ConnectorFactory::class);

		$connector = $connectorsRepository->findById(Uuid\Uuid::fromString('17c59Dfa-2edd-438e-8c49f-aa4e38e5a5e'));
		assert($connector instanceof MetadataEntities\DevicesModule\Connector);

		$factory->create($connector);

		$this->expectNotToPerformAssertions();
	}

}
