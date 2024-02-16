<?php declare(strict_types = 1);

/**
 * CapabilityState.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Queue
 * @since          1.0.0
 *
 * @date           15.07.23
 */

namespace FastyBird\Connector\NsPanel\Queue\Messages;

use DateTimeInterface;
use FastyBird\Connector\NsPanel\Types;
use FastyBird\Library\Application\ObjectMapper as ApplicationObjectMapper;
use FastyBird\Library\Metadata\Utilities as MetadataUtilities;
use Orisai\ObjectMapper;

/**
 * Device capability state definition
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Queue
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final readonly class CapabilityState implements Message
{

	public function __construct(
		#[ApplicationObjectMapper\Rules\ConsistenceEnumValue(class: Types\Capability::class)]
		private Types\Capability $capability,
		#[ApplicationObjectMapper\Rules\ConsistenceEnumValue(class: Types\Protocol::class)]
		private Types\Protocol $protocol,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\FloatValue(),
			new ObjectMapper\Rules\IntValue(),
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\BoolValue(),
			new ApplicationObjectMapper\Rules\ConsistenceEnumValue(class: Types\MotorCalibrationPayload::class),
			new ApplicationObjectMapper\Rules\ConsistenceEnumValue(class: Types\MotorControlPayload::class),
			new ApplicationObjectMapper\Rules\ConsistenceEnumValue(class: Types\PowerPayload::class),
			new ApplicationObjectMapper\Rules\ConsistenceEnumValue(class: Types\PressPayload::class),
			new ApplicationObjectMapper\Rules\ConsistenceEnumValue(class: Types\StartupPayload::class),
			new ApplicationObjectMapper\Rules\ConsistenceEnumValue(class: Types\TogglePayload::class),
			new ObjectMapper\Rules\DateTimeValue(format: DateTimeInterface::ATOM),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		                         // phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
		private int|float|string|bool|Types\MotorCalibrationPayload|Types\MotorControlPayload|Types\PowerPayload|Types\PressPayload|Types\StartupPayload|Types\TogglePayload|null $value,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private string|null $identifier = null,
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
			'value' => MetadataUtilities\Value::flattenValue($this->getValue()),
			'identifier' => $this->getIdentifier(),
		];
	}

}
