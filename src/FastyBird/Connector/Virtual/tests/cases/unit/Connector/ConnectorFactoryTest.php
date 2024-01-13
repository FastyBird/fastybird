<?php declare(strict_types = 1);

namespace FastyBird\Connector\Virtual\Tests\Cases\Unit\Connector;

use Error;
use FastyBird\Connector\Virtual\Connector;
use FastyBird\Connector\Virtual\Entities;
use FastyBird\Connector\Virtual\Exceptions;
use FastyBird\Connector\Virtual\Tests\Cases\Unit\DbTestCase;
use FastyBird\Library\Bootstrap\Exceptions as BootstrapExceptions;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;
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
		$connectorsConfigurationRepository = $this->getContainer()->getByType(
			DevicesModels\Configuration\Connectors\Repository::class,
		);

		$factory = $this->getContainer()->getByType(Connector\ConnectorFactory::class);

		$connector = $connectorsConfigurationRepository->find(
			Uuid\Uuid::fromString('93e760e1-f011-4a33-a70d-c9629706ccf8'),
		);
		assert($connector instanceof MetadataDocuments\DevicesModule\Connector);

		self::assertSame(Entities\VirtualConnector::TYPE, $connector->getType());
		self::assertSame('93e760e1-f011-4a33-a70d-c9629706ccf8', $connector->getId()->toString());

		$connector = $factory->create($connector);

		self::assertFalse($connector->hasUnfinishedTasks());
	}

}
