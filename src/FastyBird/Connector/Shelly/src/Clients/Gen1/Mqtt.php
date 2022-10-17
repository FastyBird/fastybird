<?php declare(strict_types = 1);

/**
 * Mqtt.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Clients
 * @since          0.37.0
 *
 * @date           20.07.22
 */

namespace FastyBird\Connector\Shelly\Clients\Gen1;

use FastyBird\DevicesModule\Exceptions as DevicesModuleExceptions;
use FastyBird\Metadata\Entities as MetadataEntities;
use Nette;
use function sprintf;

/**
 * MQTT client
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Clients
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Mqtt
{

	use Nette\SmartObject;

	public function __construct(
		private readonly MetadataEntities\DevicesModule\Connector $connector,
	)
	{
	}

	/**
	 * @throws DevicesModuleExceptions\Terminate
	 */
	public function connect(): void
	{
		throw new DevicesModuleExceptions\Terminate(
			sprintf('MQTT client is not implemented for connector %s', $this->connector->getIdentifier()),
		);
	}

	/**
	 * @throws DevicesModuleExceptions\Terminate
	 */
	public function disconnect(): void
	{
		throw new DevicesModuleExceptions\Terminate(
			sprintf('MQTT client is not implemented for connector %s', $this->connector->getIdentifier()),
		);
	}

}
