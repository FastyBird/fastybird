<?php declare(strict_types = 1);

/**
 * CapabilityState.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           15.07.23
 */

namespace FastyBird\Connector\NsPanel\Entities\Messages;

use DateTimeInterface;
use FastyBird\Connector\NsPanel\Types;
use FastyBird\Library\Bootstrap\ObjectMapper as BootstrapObjectMapper;
use FastyBird\Library\Metadata\Utilities as MetadataUtilities;
use Orisai\ObjectMapper;

/**
 * Device capability state definition
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class CapabilityState implements Entity
{

	public function __construct(
		#[BootstrapObjectMapper\Rules\ConsistenceEnumValue(class: Types\Capability::class)]
		private readonly Types\Capability $capability,
		#[BootstrapObjectMapper\Rules\ConsistenceEnumValue(class: Types\Protocol::class)]
		private readonly Types\Protocol $protocol,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\FloatValue(),
			new ObjectMapper\Rules\IntValue(),
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\BoolValue(),
			new BootstrapObjectMapper\Rules\ConsistenceEnumValue(class: Types\MotorCalibrationPayload::class),
			new BootstrapObjectMapper\Rules\ConsistenceEnumValue(class: Types\MotorControlPayload::class),
			new BootstrapObjectMapper\Rules\ConsistenceEnumValue(class: Types\PowerPayload::class),
			new BootstrapObjectMapper\Rules\ConsistenceEnumValue(class: Types\PressPayload::class),
			new BootstrapObjectMapper\Rules\ConsistenceEnumValue(class: Types\StartupPayload::class),
			new BootstrapObjectMapper\Rules\ConsistenceEnumValue(class: Types\TogglePayload::class),
			new ObjectMapper\Rules\DateTimeValue(format: DateTimeInterface::ATOM),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		                                  // phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
		private readonly int|float|string|bool|Types\MotorCalibrationPayload|Types\MotorControlPayload|Types\PowerPayload|Types\PressPayload|Types\StartupPayload|Types\TogglePayload|null $value,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private readonly string|null $identifier = null,
	)
	{
	}

	public function getCapability(): Types\Capability
	{
		return $this->capability;
	}

	public function getProtocol(): Types\Protocol
	{
		return $this->protocol;
	}

	// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
	public function getValue(): int|float|string|bool|Types\MotorCalibrationPayload|Types\MotorControlPayload|Types\PowerPayload|Types\PressPayload|Types\StartupPayload|Types\TogglePayload|null
	{
		return $this->value;
	}

	public function getIdentifier(): string|null
	{
		return $this->identifier;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'capability' => $this->getCapability()->getValue(),
			'protocol' => $this->getProtocol()->getValue(),
			'value' => MetadataUtilities\ValueHelper::flattenValue($this->getValue()),
			'identifier' => $this->getIdentifier(),
		];
	}

}
