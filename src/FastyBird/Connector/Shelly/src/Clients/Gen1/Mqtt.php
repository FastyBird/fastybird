<?php declare(strict_types = 1);

/**
 * Mqtt.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Clients
 * @since          0.37.0
 *
 * @date           20.07.22
 */

namespace FastyBird\Connector\Shelly\Clients\Gen1;

use FastyBird\Library\Metadata\Entities as MetadataEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
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
	 * @throws DevicesExceptions\Terminate
	 */
	public function connect(): void
	{
		throw new DevicesExceptions\Terminate(
			sprintf('MQTT client is not implemented for connector %s', $this->connector->getIdentifier()),
		);
	}

	/**
	 * @throws DevicesExceptions\Terminate
	 */
	public function disconnect(): void
	{
		throw new DevicesExceptions\Terminate(
			sprintf('MQTT client is not implemented for connector %s', $this->connector->getIdentifier()),
		);
	}

}
