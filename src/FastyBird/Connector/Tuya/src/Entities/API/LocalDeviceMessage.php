<?php declare(strict_types = 1);

/**
 * LocalDeviceMessage.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           26.04.22
 */

namespace FastyBird\Connector\Tuya\Entities\API;

use FastyBird\Connector\Tuya\Types;
use FastyBird\Library\Bootstrap\ObjectMapper as BootstrapObjectMapper;
use Orisai\ObjectMapper;
use function array_map;
use function is_array;

/**
 * Local api device raw message entity
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class LocalDeviceMessage implements Entity
{

	/**
	 * @param string|LocalDeviceWifiScan|array<DeviceDataPointState>|null $data
	 */
	public function __construct(
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $identifier,
		#[BootstrapObjectMapper\Rules\ConsistenceEnumValue(class: Types\LocalDeviceCommand::class)]
		private readonly Types\LocalDeviceCommand $command,
		#[ObjectMapper\Rules\IntValue(unsigned: true)]
		private readonly int $sequence,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\IntValue(unsigned: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		#[ObjectMapper\Modifiers\FieldName('return_code')]
		private readonly int|null $returnCode,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\MappedObjectValue(class: LocalDeviceWifiScan::class),
			new ObjectMapper\Rules\ArrayOf(
				new ObjectMapper\Rules\MappedObjectValue(DeviceDataPointState::class),
			),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private readonly string|array|LocalDeviceWifiScan|null $data = null,
		#[ObjectMapper\Rules\AnyOf([
			new BootstrapObjectMapper\Rules\ConsistenceEnumValue(class: Types\LocalDeviceError::class),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private readonly Types\LocalDeviceError|null $error = null,
	)
	{
	}

	public function getIdentifier(): string
	{
		return $this->identifier;
	}

	public function getCommand(): Types\LocalDeviceCommand
	{
		return $this->command;
	}

	public function getSequence(): int
	{
		return $this->sequence;
	}

	public function getReturnCode(): int|null
	{
		return $this->returnCode;
	}

	/**
	 * @return string|array<DeviceDataPointState>|LocalDeviceWifiScan|null
	 */
	public function getData(): string|array|LocalDeviceWifiScan|null
	{
		return $this->data;
	}

	public function getError(): Types\LocalDeviceError|null
	{
		return $this->error;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'identifier' => $this->getIdentifier(),
			'command' => $this->getCommand()->getValue(),
			'sequence' => $this->getSequence(),
			'return_code' => $this->getReturnCode(),
			'data' => $this->getData() instanceof Entity ? $this->getData()->toArray() : (is_array(
				$this->getData(),
			) ? array_map(
				static fn (
					Entity $entity,
				): array => $entity->toArray(),
				$this->getData(),
			) : $this->getData()),
			'error' => $this->getError()?->getValue(),
		];
	}

}
