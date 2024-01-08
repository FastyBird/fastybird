<?php declare(strict_types = 1);

namespace FastyBird\Connector\FbMqtt\Tests\Cases\Unit\Connector;

use Error;
use FastyBird\Connector\FbMqtt\Connector;
use FastyBird\Connector\FbMqtt\Entities;
use FastyBird\Connector\FbMqtt\Exceptions;
use FastyBird\Connector\FbMqtt\Tests\Cases\Unit\DbTestCase;
use FastyBird\Library\Bootstrap\Exceptions as BootstrapExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
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

		$connector = $connectorsRepository->find(
			Uuid\Uuid::fromString('37b86cdc-376b-4d4c-9683-aa4f41daa13a'),
			Entities\FbMqttConnector::class,
		);
		assert($connector instanceof Entities\FbMqttConnector);

		$factory->create($connector);

		$this->expectNotToPerformAssertions();
	}

}
