<?php declare(strict_types = 1);

/**
 * AfterConnectorStart.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Events
 * @since          0.65.0
 *
 * @date           22.06.22
 */

namespace FastyBird\Module\Devices\Events;

use FastyBird\Library\Metadata\Entities as MetadataEntities;
use Symfony\Contracts\EventDispatcher;

/**
 * Event fired after connector has been started
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Events
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class AfterConnectorStart extends EventDispatcher\Event
{

	public function __construct(
		private readonly MetadataEntities\DevicesModule\Connector $connector,
	)
	{
	}

	public function getConnector(): MetadataEntities\DevicesModule\Connector
	{
		return $this->connector;
	}

}
