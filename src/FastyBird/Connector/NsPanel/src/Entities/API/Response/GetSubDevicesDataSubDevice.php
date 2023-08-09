<?php declare(strict_types = 1);

/**
 * GetSubDevicesDataSubDevice.php
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

namespace FastyBird\Connector\NsPanel\Entities\API\Response;

use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Connector\NsPanel\Types;
use FastyBird\Library\Bootstrap\ObjectMapper as BootstrapObjectMapper;
use Orisai\ObjectMapper;
use Ramsey\Uuid;
use stdClass;
use function array_map;
use function is_array;

/**
 * Get NS Panel sub-devices list data sub-device response definition
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class GetSubDevicesDataSubDevice implements Entities\API\Entity
{

	/**
	 * @param array<Entities\API\Capability> $capabilities
	 * @param array<string, string|array<string, string>> $tags
	 */
	public function __construct(
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
		#[ObjectMapper\Rules\MappedObjectValue(Entities\API\State::class)]
		private readonly Entities\API\State $state,
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
			new ObjectMapper\Rules\MappedObjectValue(Entities\API\Capability::class),
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
	 * @return array<Entities\API\Capability>
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
	 * @return array<string|int, Entities\API\States\State>
	 */
	public function getState(): array
	{
		return $this->state->getStates();
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
				static fn (Entities\API\Capability $capability): array => $capability->toArray(),
				$this->getCapabilities(),
			),
			'protocol' => $this->getProtocol(),
			'state' => $this->state->toArray(),
			'tags' => $this->getTags(),
			'online' => $this->isOnline(),
			'subnet' => $this->isInSubnet(),
		];
	}

	public function toJson(): object
	{
		$tags = new stdClass();

		foreach ($this->getTags() as $name => $value) {
			if (is_array($value)) {
				$tags->{$name} = new stdClass();

				foreach ($value as $subName => $subValue) {
					$tags->{$name}->{$subName} = $subValue;
				}
			} else {
				$tags->{$name} = $value;
			}
		}

		$json = new stdClass();
		$json->serial_number = $this->getSerialNumber();
		if ($this->getThirdSerialNumber() !== null) {
			$json->third_serial_number = $this->getThirdSerialNumber()->toString();
			$json->service_address = $this->getServiceAddress();
		}

		$json->name = $this->getName();
		$json->manufacturer = $this->getManufacturer();
		$json->model = $this->getModel();
		$json->firmware_version = $this->getFirmwareVersion();
		if ($this->getHostname() !== null) {
			$json->hostname = $this->getHostname();
		}

		if ($this->getMacAddress() !== null) {
			$json->mac_address = $this->getMacAddress();
		}

		if ($this->getAppName() !== null) {
			$json->app_name = $this->getAppName();
		}

		$json->display_category = $this->getDisplayCategory()->getValue();
		$json->capabilities = array_map(
			static fn (Entities\API\Capability $capability): object => $capability->toJson(),
			$this->getCapabilities(),
		);
		if ($this->getThirdSerialNumber() === null) {
			$json->protocol = $this->getProtocol();
		}

		$json->state = $this->state->toJson();
		$json->tags = $tags;
		$json->online = $this->isOnline();
		if ($this->isInSubnet() !== null) {
			$json->subnet = $this->isInSubnet();
		}

		return $json;
	}

}
