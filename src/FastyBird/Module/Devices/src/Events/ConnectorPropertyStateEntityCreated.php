<?php declare(strict_types = 1);

/**
 * ConnectorPropertyStateEntityCreated.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Events
 * @since          1.0.0
 *
 * @date           29.07.23
 */

namespace FastyBird\Module\Devices\Events;

use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Module\Devices\States;
use Symfony\Contracts\EventDispatcher;

/**
 * Connector property state entity was created event
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Events
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class ConnectorPropertyStateEntityCreated extends EventDispatcher\Event
{

	public function __construct(
		private readonly MetadataDocuments\DevicesModule\ConnectorDynamicProperty $property,
		private readonly States\ConnectorProperty $read,
		private readonly States\ConnectorProperty $get,
	)
	{
	}

	public function getProperty(): MetadataDocuments\DevicesModule\ConnectorDynamicProperty
	{
		return $this->property;
	}

	public function getRead(): States\ConnectorProperty
	{
		return $this->read;
	}

	public function getGet(): States\ConnectorProperty
	{
		return $this->get;
	}

}
