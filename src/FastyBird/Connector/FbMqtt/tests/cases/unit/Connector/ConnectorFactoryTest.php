<?php declare(strict_types = 1);

namespace FastyBird\Connector\FbMqtt\Tests\Cases\Unit\Connector;

use Exception;
use FastyBird\Connector\FbMqtt\Connector;
use FastyBird\Connector\FbMqtt\Entities;
use FastyBird\Connector\FbMqtt\Exceptions;
use FastyBird\Connector\FbMqtt\Tests\Cases\Unit\DbTestCase;
use FastyBird\Module\Devices\DataStorage as DevicesDataStorage;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
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
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 */
	public function testCreateConnector(): void
	{
		$connectorsRepository = $this->getContainer()->getByType(
			DevicesModels\Connectors\ConnectorsRepository::class,
		);

		$factory = $this->getContainer()->getByType(Connector\ConnectorFactory::class);

		$findConnectorQuery = new DevicesQueries\FindConnectors();
		$findConnectorQuery->byId(Uuid\Uuid::fromString('17c59Dfa-2edd-438e-8c49f-aa4e38e5a5e'));

		$connector = $connectorsRepository->findOneBy($findConnectorQuery);
		assert($connector instanceof Entities\FbMqttConnector);

		$factory->create($connector);

		$this->expectNotToPerformAssertions();
	}

}
