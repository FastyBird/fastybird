<?php declare(strict_types = 1);

/**
 * DiscoveredSubDevice.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Clients
 * @since          1.0.0
 *
 * @date           23.07.23
 */

namespace FastyBird\Connector\NsPanel\Clients\Messages\Response;

use FastyBird\Connector\NsPanel\Clients;
use FastyBird\Connector\NsPanel\Types;
use FastyBird\Library\Application\ObjectMapper as ApplicationObjectMapper;
use Orisai\ObjectMapper;
use Ramsey\Uuid;
use function array_map;

/**
 * NS Panel sub-device description - both NS Panel connected & third-party
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Clients
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final readonly class DiscoveredSubDevice implements Clients\Messages\Message
{

	/**
	 * @param array<CapabilityDescription> $capabilities
	 * @param array<string, string|array<string, string>> $tags
	 */
	public function __construct(
		#[ApplicationObjectMapper\Rules\UuidValue()]
		private Uuid\UuidInterface $parent,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		#[ObjectMapper\Modifiers\FieldName('serial_number')]
		private string $serialNumber,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private string $name,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private string $manufacturer,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private string $model,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		#[ObjectMapper\Modifiers\FieldName('firmware_version')]
		private string $firmwareVersion,
		#[ApplicationObjectMapper\Rules\ConsistenceEnumValue(class: Types\Category::class)]
		#[ObjectMapper\Modifiers\FieldName('display_category')]
		private Types\Category $displayCategory,
		#[ObjectMapper\Rules\AnyOf([
			new ApplicationObjectMapper\Rules\UuidValue(),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		#[ObjectMapper\Modifiers\FieldName('third_serial_number')]
		private Uuid\UuidInterface|null $thirdSerialNumber = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		#[ObjectMapper\Modifiers\FieldName('service_address')]
		private string|null $serviceAddress = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private string|null $hostname = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		#[ObjectMapper\Modifiers\FieldName('mac_address')]
		private string|null $macAddress = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		#[ObjectMapper\Modifiers\FieldName('app_name')]
		private string|null $appName = null,
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\MappedObjectValue(CapabilityDescription::class),
		)]
		private array $capabilities = [],
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private string|null $protocol = null,
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
		private array $tags = [],
		#[ObjectMapper\Rules\BoolValue()]
		private bool $online = false,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\BoolValue(),
			new ObjectMapper\Rules\NullValue(),
		])]
		private bool|null $subnet = null,
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
