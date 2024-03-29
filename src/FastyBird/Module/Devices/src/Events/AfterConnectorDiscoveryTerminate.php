<?php declare(strict_types = 1);

/**
 * AfterConnectorDiscoveryTerminate.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Events
 * @since          1.0.0
 *
 * @date           22.06.22
 */

namespace FastyBird\Module\Devices\Events;

use FastyBird\Module\Devices\Connectors;
use FastyBird\Module\Devices\Documents;
use Symfony\Contracts\EventDispatcher;

/**
 * Event fired after connector discovery has been terminated
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Events
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class AfterConnectorDiscoveryTerminate extends EventDispatcher\Event
{

	public function __construct(
		private readonly Connectors\Connector $service,
		private readonly Documents\Connectors\Connector $connector,
	)
	{
	}

	public function getService(): Connectors\Connector
	{
		return $this->service;
	}

	public function getConnector(): Documents\Connectors\Connector
	{
		return $this->connector;
	}

}
