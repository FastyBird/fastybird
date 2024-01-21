<?php declare(strict_types = 1);

/**
 * AfterConnectorExecutionTerminate.php
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

use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Module\Devices\Connectors;
use Symfony\Contracts\EventDispatcher;

/**
 * Event fired after connector has been terminated
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Events
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class AfterConnectorExecutionTerminate extends EventDispatcher\Event
{

	public function __construct(
		private readonly Connectors\Connector $service,
		private readonly MetadataDocuments\DevicesModule\Connector $connector,
	)
	{
	}

	public function getService(): Connectors\Connector
	{
		return $this->service;
	}

	public function getConnector(): MetadataDocuments\DevicesModule\Connector
	{
		return $this->connector;
	}

}
