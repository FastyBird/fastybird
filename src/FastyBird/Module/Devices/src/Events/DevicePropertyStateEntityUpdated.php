<?php declare(strict_types = 1);

/**
 * DevicePropertyStateEntityUpdated.php
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
use FastyBird\Module\Devices\Entities;
use FastyBird\Module\Devices\States;
use Symfony\Contracts\EventDispatcher;

/**
 * Device property state entity was updated event
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Events
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class DevicePropertyStateEntityUpdated extends EventDispatcher\Event
{

	public function __construct(
		// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
		private readonly MetadataDocuments\DevicesModule\DeviceDynamicProperty|MetadataDocuments\DevicesModule\DeviceMappedProperty|Entities\Devices\Properties\Dynamic|Entities\Devices\Properties\Mapped $property,
		private readonly States\DeviceProperty $previousState,
		private readonly States\DeviceProperty $state,
	)
	{
	}
	// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
	public function getProperty(): MetadataDocuments\DevicesModule\DeviceDynamicProperty|MetadataDocuments\DevicesModule\DeviceMappedProperty|Entities\Devices\Properties\Dynamic|Entities\Devices\Properties\Mapped
	{
		return $this->property;
	}

	public function getPreviousState(): States\DeviceProperty
	{
		return $this->previousState;
	}

	public function getState(): States\DeviceProperty
	{
		return $this->state;
	}

}
