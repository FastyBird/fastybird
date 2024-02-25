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
		#[ObjectMapper\Rules\BackedEnumValue(class: Types\Capability::class)]
		private Types\Capability $capability,
		#[ObjectMapper\Rules\BackedEnumValue(class: Types\Protocol::class)]
		private Types\Protocol $protocol,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\FloatValue(),
			new ObjectMapper\Rules\IntValue(),
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\BoolValue(),
			new ObjectMapper\Rules\BackedEnumValue(class: Types\Payloads\MotorCalibrationPayload::class),
			new ObjectMapper\Rules\BackedEnumValue(class: Types\Payloads\MotorControlPayload::class),
			new ObjectMapper\Rules\BackedEnumValue(class: Types\Payloads\PowerPayload::class),
			new ObjectMapper\Rules\BackedEnumValue(class: Types\Payloads\PressPayload::class),
			new ObjectMapper\Rules\BackedEnumValue(class: Types\Payloads\StartupPayload::class),
			new ObjectMapper\Rules\BackedEnumValue(class: Types\Payloads\TogglePayload::class),
			new ObjectMapper\Rules\DateTimeValue(format: DateTimeInterface::ATOM),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private int|float|string|bool|Types\Payloads\MotorCalibrationPayload|Types\Payloads\Payload|null $value,
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

	public function getValue(): int|float|string|bool|Types\Payloads\Payload|null
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
			'capability' => $this->getCapability()->value,
			'protocol' => $this->getProtocol()->value,
			'value' => MetadataUtilities\Value::flattenValue($this->getValue()),
			'identifier' => $this->getIdentifier(),
		];
	}

}
