<?php declare(strict_types = 1);

/**
 * Mqtt.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Clients
 * @since          1.0.0
 *
 * @date           20.07.22
 */

namespace FastyBird\Connector\Shelly\Clients;

use FastyBird\Connector\Shelly\Documents;
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
final class Mqtt implements Client
{

	use Nette\SmartObject;

	public function __construct(private readonly Documents\Connectors\Connector $connector)
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
