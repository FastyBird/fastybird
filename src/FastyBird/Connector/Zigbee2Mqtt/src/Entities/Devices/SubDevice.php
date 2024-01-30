<?php declare(strict_types = 1);

/**
 * SubDevice.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           25.12.23
 */

namespace FastyBird\Connector\Zigbee2Mqtt\Entities\Devices;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Connector\Zigbee2Mqtt\Entities;
use FastyBird\Connector\Zigbee2Mqtt\Exceptions;
use FastyBird\Connector\Zigbee2Mqtt\Types;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use Ramsey\Uuid;
use function assert;
use function count;
use function is_bool;
use function is_string;

/**
 * @ORM\Entity
 */
class SubDevice extends Entities\Zigbee2MqttDevice
{

	public const TYPE = 'zigbee2mqtt-connector-sub-device';

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function __construct(
		string $identifier,
		Bridge $parent,
		DevicesEntities\Connectors\Connector $connector,
		string|null $name = null,
		Uuid\UuidInterface|null $id = null,
	)
	{
		parent::__construct($identifier, $connector, $name, $id);

		$this->setParents([$parent]);
	}

	public static function getType(): string
	{
		return self::TYPE;
	}

	public function getDiscriminatorName(): string
	{
		return self::TYPE;
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function getBridge(): Bridge
	{
		foreach ($this->parents->toArray() as $parent) {
			if ($parent instanceof Bridge) {
				return $parent;
			}
		}

		throw new Exceptions\InvalidState('Sub-device have to have parent bridge defined');
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function setParents(array $parents): void
	{
		if (count($parents) !== 1 || !$parents[0] instanceof Bridge) {
			throw new Exceptions\InvalidState('Sub-device could have only one parent and it have to be gateway');
		}

		parent::setParents($parents);
	}

	/**
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function getIeeeAddress(): string
	{
		$property = $this->properties
			->filter(
			// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				static fn (DevicesEntities\Devices\Properties\Property $property): bool => $property->getIdentifier() === Types\DevicePropertyIdentifier::IEEE_ADDRESS
			)
			->first();

		if (
			$property instanceof DevicesEntities\Devices\Properties\Variable
			&& is_string($property->getValue())
		) {
			return $property->getValue();
		}

		throw new MetadataExceptions\InvalidState('Device IEEE addrress is not configured');
	}

	/**
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function getHardwareType(): Types\DeviceType
	{
		$property = $this->properties
			->filter(
			// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				static fn (DevicesEntities\Devices\Properties\Property $property): bool => $property->getIdentifier() === Types\DevicePropertyIdentifier::TYPE
			)
			->first();

		if (
			$property instanceof DevicesEntities\Devices\Properties\Variable
			&& is_string($property->getValue())
			&& Types\DeviceType::isValidValue($property->getValue())
		) {
			return Types\DeviceType::get($property->getValue());
		}

		throw new MetadataExceptions\InvalidState('Device hardware type is not configured');
	}

	/**
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function getHardwareModel(): string|null
	{
		$property = $this->properties
			->filter(
			// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				static fn (DevicesEntities\Devices\Properties\Property $property): bool => $property->getIdentifier() === Types\DevicePropertyIdentifier::MODEL
			)
			->first();

		if (!$property instanceof DevicesEntities\Devices\Properties\Variable || $property->getValue() === null) {
			return null;
		}

		$value = $property->getValue();
		assert(is_string($value));

		return $value;
	}

	/**
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function getHardwareManufacturer(): string|null
	{
		$property = $this->properties
			->filter(
			// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				static fn (DevicesEntities\Devices\Properties\Property $property): bool => $property->getIdentifier() === Types\DevicePropertyIdentifier::MANUFACTURER
			)
			->first();

		if (!$property instanceof DevicesEntities\Devices\Properties\Variable || $property->getValue() === null) {
			return null;
		}

		$value = $property->getValue();
		assert(is_string($value));

		return $value;
	}

	/**
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function isSupported(): bool
	{
		$property = $this->properties
			->filter(
			// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				static fn (DevicesEntities\Devices\Properties\Property $property): bool => $property->getIdentifier() === Types\DevicePropertyIdentifier::SUPPORTED
			)
			->first();

		if (
			$property instanceof DevicesEntities\Devices\Properties\Variable
			&& is_bool($property->getValue())
		) {
			return $property->getValue();
		}

		return false;
	}

	/**
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function isDisabled(): bool
	{
		$property = $this->properties
			->filter(
			// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				static fn (DevicesEntities\Devices\Properties\Property $property): bool => $property->getIdentifier() === Types\DevicePropertyIdentifier::DISABLED
			)
			->first();

		if (
			$property instanceof DevicesEntities\Devices\Properties\Variable
			&& is_bool($property->getValue())
		) {
			return $property->getValue();
		}

		return false;
	}

}
