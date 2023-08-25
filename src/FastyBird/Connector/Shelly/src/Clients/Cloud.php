<?php declare(strict_types = 1);

/**
 * Cloud.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Clients
 * @since          1.0.0
 *
 * @date           03.01.23
 */

namespace FastyBird\Connector\Shelly\Clients;

use FastyBird\Connector\Shelly\Entities;
use FastyBird\Library\Metadata\Entities as MetadataEntities;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use Nette;
use React\Promise;
use function sprintf;

/**
 * Cloud client
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Clients
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Cloud implements Client
{

	use Nette\SmartObject;

	public function __construct(
		private readonly Entities\ShellyConnector $connector,
	)
	{
	}

	/**
	 * @throws DevicesExceptions\Terminate
	 */
	public function connect(): void
	{
		throw new DevicesExceptions\Terminate(
			sprintf('Cloud client is not implemented for connector %s', $this->connector->getIdentifier()),
		);
	}

	/**
	 * @throws DevicesExceptions\Terminate
	 */
	public function disconnect(): void
	{
		throw new DevicesExceptions\Terminate(
			sprintf('Cloud client is not implemented for connector %s', $this->connector->getIdentifier()),
		);
	}

}
