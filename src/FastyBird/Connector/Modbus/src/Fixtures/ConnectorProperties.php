<?php declare(strict_types = 1);

/**
 * ConnectorProperties.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ModbusConnector!
 * @subpackage     Fixtures
 * @since          0.34.0
 *
 * @date           22.08.22
 */

namespace FastyBird\Connector\Modbus\Fixtures;

use Doctrine\Common\DataFixtures;
use Doctrine\Persistence;
use FastyBird\Connector\Modbus\Entities;
use FastyBird\Connector\Modbus\Exceptions;
use FastyBird\Connector\Modbus\Types;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;

/**
 * Connector properties database fixture
 *
 * @package        FastyBird:ModbusConnector!
 * @subpackage     Fixtures
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ConnectorProperties extends DataFixtures\AbstractFixture implements DataFixtures\DependentFixtureInterface
{

	/**
	 * @throws Exceptions\InvalidState
	 * @throws DevicesExceptions\InvalidState
	 */
	public function load(Persistence\ObjectManager $manager): void
	{
		$connector = $this->getReference('modbus-rtu-connector');

		if (!$connector instanceof Entities\ModbusConnector) {
			throw new Exceptions\InvalidState('Connector reference could not be loaded');
		}

		$clientModeProperty = new DevicesEntities\Connectors\Properties\Variable(
			$connector,
			Types\ConnectorPropertyIdentifier::IDENTIFIER_CLIENT_MODE,
		);
		$clientModeProperty->setDataType(MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING));
		$clientModeProperty->setValue(Types\ClientMode::MODE_RTU);

		$interfaceProperty = new DevicesEntities\Connectors\Properties\Variable(
			$connector,
			Types\ConnectorPropertyIdentifier::IDENTIFIER_RTU_INTERFACE,
		);
		$interfaceProperty->setDataType(MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING));
		$interfaceProperty->setValue('/dev/ttyUSB0');

		$manager->persist($clientModeProperty);
		$manager->persist($interfaceProperty);
		$manager->flush();
	}

	/**
	 * {@inheritDoc}
	 */
	public function getDependencies(): array
	{
		return [
			Connector::class,
		];
	}

}
