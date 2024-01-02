<?php declare(strict_types = 1);

namespace FastyBird\Connector\Zigbee2Mqtt\Tests\Cases\Unit\Connector;

use Error;
use FastyBird\Connector\Zigbee2Mqtt\Connector;
use FastyBird\Connector\Zigbee2Mqtt\Entities;
use FastyBird\Connector\Zigbee2Mqtt\Exceptions;
use FastyBird\Connector\Zigbee2Mqtt\Tests\Cases\Unit\DbTestCase;
use FastyBird\Library\Bootstrap\Exceptions as BootstrapExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use Nette;
use Ramsey\Uuid;
use RuntimeException;
use function assert;

final class ConnectorFactoryTest extends DbTestCase
{

	/**
	 * @throws BootstrapExceptions\InvalidArgument
	 * @throws Exceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 */
	public function testCreateConnector(): void
	{
		$connectorsRepository = $this->getContainer()->getByType(
			DevicesModels\Entities\Connectors\ConnectorsRepository::class,
		);

		$factory = $this->getContainer()->getByType(Connector\ConnectorFactory::class);

		$findConnectorQuery = new DevicesQueries\Entities\FindConnectors();
		$findConnectorQuery->byId(Uuid\Uuid::fromString('0640b179-ee92-4766-a491-70e02e9e2378'));

		$connector = $connectorsRepository->findOneBy($findConnectorQuery);
		assert($connector instanceof Entities\Zigbee2MqttConnector);

		self::assertSame('0640b179-ee92-4766-a491-70e02e9e2378', $connector->getId()->toString());

		$factory->create($connector);

		$this->expectNotToPerformAssertions();
	}

}
