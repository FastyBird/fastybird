<?php declare(strict_types = 1);

/**
 * DiscoveredSubDevice.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           23.07.23
 */

namespace FastyBird\Connector\NsPanel\Entities\Clients;

use FastyBird\Connector\NsPanel\Types;
use FastyBird\Library\Bootstrap\ObjectMapper as BootstrapObjectMapper;
use Orisai\ObjectMapper;
use Ramsey\Uuid;
use function array_map;

/**
 * NS Panel sub-device description - both NS Panel connected & third-party
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DiscoveredSubDevice implements Entity
{

	/**
	 * @param array<CapabilityDescription> $capabilities
	 * @param array<string, string|array<string, string>> $tags
	 */
	public function __construct(
		#[BootstrapObjectMapper\Rules\UuidValue()]
		private readonly Uuid\UuidInterface $parent,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		#[ObjectMapper\Modifiers\FieldName('serial_number')]
		private readonly string $serialNumber,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $name,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $manufacturer,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $model,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		#[ObjectMapper\Modifiers\FieldName('firmware_version')]
		private readonly string $firmwareVersion,
		#[BootstrapObjectMapper\Rules\ConsistenceEnumValue(class: Types\Category::class)]
		#[ObjectMapper\Modifiers\FieldName('display_category')]
		private readonly Types\Category $displayCategory,
		#[ObjectMapper\Rules\AnyOf([
			new BootstrapObjectMapper\Rules\UuidValue(),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		#[ObjectMapper\Modifiers\FieldName('third_serial_number')]
		private readonly Uuid\UuidInterface|null $thirdSerialNumber = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		#[ObjectMapper\Modifiers\FieldName('service_address')]
		private readonly string|null $serviceAddress = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private readonly string|null $hostname = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		#[ObjectMapper\Modifiers\FieldName('mac_address')]
		private readonly string|null $macAddress = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		#[ObjectMapper\Modifiers\FieldName('app_name')]
		private readonly string|null $appName = null,
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\MappedObjectValue(CapabilityDescription::class),
		)]
		private readonly array $capabilities = [],
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private readonly string|null $protocol = null,
		#[ObjectMapper\Rules\ArrayOf(
			item: new ObjectMapper\Rules\AnyOf([
				new ObjectMapper\Rules\StringValue(),
				new ObjectMapper\Rules\ArrayOf(
					item: new ObjectMapper\Rules\StringValue(),
					key: new ObjectMapper\Rules\AnyOf([
						new ObjectMapper\Rules\StringValue(),
						new ObjectMapper\Rules\IntValue(),
					]),
				),
			]),
			key: new ObjectMapper\Rules\StringValue(),
		)]
		private readonly array $tags = [],
		#[ObjectMapper\Rules\BoolValue()]
		private readonly bool $online = false,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\BoolValue(),
			new ObjectMapper\Rules\NullValue(),
		])]
		private readonly bool|null $subnet = null,
	)
	{
	}

	public function getParent(): Uuid\UuidInterface
	{
		return $this->parent;
	}

	public function getSerialNumber(): string
	{
		return $this->serialNumber;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getManufacturer(): string
	{
		return $this->manufacturer;
	}

	public function getModel(): string
	{
		return $this->model;
	}

	public function getFirmwareVersion(): string
	{
		return $this->firmwareVersion;
	}

	public function getDisplayCategory(): Types\Category
	{
		return $this->displayCategory;
	}

	public function getThirdSerialNumber(): Uuid\UuidInterface|null
	{
		return $this->thirdSerialNumber;
	}

	public function getServiceAddress(): string|null
	{
		return $this->serviceAddress;
	}

	public function getHostname(): string|null
	{
		return $this->hostname;
	}

	public function getMacAddress(): string|null
	{
		return $this->macAddress;
	}

	public function getAppName(): string|null
	{
		return $this->appName;
	}

	/**
	 * @return array<CapabilityDescription>
	 */
	public function getCapabilities(): array
	{
		return $this->capabilities;
	}

	public function getProtocol(): string|null
	{
		return $this->protocol;
	}

	/**
	 * @return array<string, string|array<string, string>>
	 */
	public function getTags(): array
	{
		return $this->tags;
	}

	public function isOnline(): bool
	{
		return $this->online;
	}

	public function isInSubnet(): bool|null
	{
		return $this->subnet;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'serial_number' => $this->getSerialNumber(),
			'third_serial_number' => $this->getThirdSerialNumber()?->toString(),
			'service_address' => $this->getServiceAddress(),
			'name' => $this->getName(),
			'manufacturer' => $this->getManufacturer(),
			'model' => $this->getModel(),
			'firmware_version' => $this->getFirmwareVersion(),
			'hostname' => $this->getHostname(),
			'mac_address' => $this->getMacAddress(),
			'app_name' => $this->getAppName(),
			'display_category' => $this->getDisplayCategory()->getValue(),
			'capabilities' => array_map(
				static fn (CapabilityDescription $capability): array => $capability->toArray(),
				$this->getCapabilities(),
			),
			'protocol' => $this->getProtocol(),
			'tags' => $this->getTags(),
			'online' => $this->isOnline(),
			'subnet' => $this->isInSubnet(),
		];
	}

}
