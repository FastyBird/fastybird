<?php declare(strict_types = 1);

/**
 * Property.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Helpers
 * @since          1.0.0
 *
 * @date           14.05.23
 */

namespace FastyBird\Connector\NsPanel\Helpers;

use DateTimeInterface;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use Nette;
use Nette\Utils;

/**
 * Useful dynamic property state helpers
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Helpers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Property
{

	use Nette\SmartObject;

	public function __construct(
		private readonly DevicesUtilities\DevicePropertiesStates $devicePropertiesStateManager,
		private readonly DevicesUtilities\ChannelPropertiesStates $channelPropertiesStateManager,
	)
	{
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function setValue(
		DevicesEntities\Devices\Properties\Dynamic|DevicesEntities\Channels\Properties\Dynamic $property,
		Utils\ArrayHash $data,
	): void
	{
		if ($property instanceof DevicesEntities\Devices\Properties\Dynamic) {
			$this->devicePropertiesStateManager->setValue($property, $data);
		} else {
			$this->channelPropertiesStateManager->setValue($property, $data);
		}
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function getActualValue(
		// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
		DevicesEntities\Devices\Properties\Dynamic|DevicesEntities\Devices\Properties\Mapped|DevicesEntities\Channels\Properties\Dynamic|DevicesEntities\Channels\Properties\Mapped $property,
	): bool|float|int|string|DateTimeInterface|MetadataTypes\ButtonPayload|MetadataTypes\SwitchPayload|MetadataTypes\CoverPayload|null
	{
		return $property instanceof DevicesEntities\Devices\Properties\Dynamic || $property instanceof DevicesEntities\Devices\Properties\Mapped
			? $this->devicePropertiesStateManager->getValue($property)?->getActualValue()
			: $this->channelPropertiesStateManager->getValue(
				$property,
			)?->getActualValue();
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function getExpectedValue(
		// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
		DevicesEntities\Devices\Properties\Dynamic|DevicesEntities\Devices\Properties\Mapped|DevicesEntities\Channels\Properties\Dynamic|DevicesEntities\Channels\Properties\Mapped $property,
	): bool|float|int|string|DateTimeInterface|MetadataTypes\ButtonPayload|MetadataTypes\SwitchPayload|MetadataTypes\CoverPayload|null
	{
		return $property instanceof DevicesEntities\Devices\Properties\Dynamic || $property instanceof DevicesEntities\Devices\Properties\Mapped
			? $this->devicePropertiesStateManager->getValue($property)?->getExpectedValue()
			: $this->channelPropertiesStateManager->getValue(
				$property,
			)?->getExpectedValue();
	}

}
