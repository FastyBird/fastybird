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
use Nette;
use stdClass;
use function array_map;
use function is_array;

/**
 * NS Panel sub-device description - both NS Panel connected & third-party
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class GetSubDevicesDataSubDevice implements Entities\API\Entity
{

	use Nette\SmartObject;

	/**
	 * @param array<Entities\API\Capability> $capabilities
	 * @param array<Entities\API\Statuses\Status> $state
	 * @param array<string, string|array<string, string>> $tags
	 */
	public function __construct(
		private readonly string $serialNumber,
		private readonly string $name,
		private readonly string $manufacturer,
		private readonly string $model,
		private readonly string $firmwareVersion,
		private readonly Types\Category $displayCategory,
		private readonly string|null $thirdSerialNumber = null,
		private readonly string|null $serviceAddress = null,
		private readonly string|null $hostname = null,
		private readonly string|null $macAddress = null,
		private readonly string|null $appName = null,
		private readonly array $capabilities = [],
		private readonly string|null $protocol = null,
		private readonly array $state = [],
		private readonly array $tags = [],
		private readonly bool $online = false,
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

	public function getThirdSerialNumber(): string|null
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
	 * @return array<Entities\API\Statuses\Status>
	 */
	public function getStatuses(): array
	{
		return $this->state;
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
			'third_serial_number' => $this->getThirdSerialNumber(),
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
			'state' => array_map(
				static fn (Entities\API\Statuses\Status $state): array => $state->toArray(),
				$this->getStatuses(),
			),
			'tags' => $this->getTags(),
			'online' => $this->isOnline(),
			'subnet' => $this->isInSubnet(),
		];
	}

	public function toJson(): object
	{
		$state = new stdClass();

		foreach ($this->getStatuses() as $item) {
			if ($item->getType()->equalsValue(Types\Capability::TOGGLE)) {
				if (!$state->{$item->getType()->getValue()} instanceof stdClass) {
					$state->{$item->getType()->getValue()} = new stdClass();
				}

				$state->{$item->getType()->getValue()}->{$item->getName()} = $item->toJson();
			} else {
				$state->{$item->getType()->getValue()} = $item->toJson();
			}
		}

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
			$json->third_serial_number = $this->getThirdSerialNumber();
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

		$json->state = $state;
		$json->tags = $tags;
		$json->online = $this->isOnline();
		if ($this->isInSubnet() !== null) {
			$json->subnet = $this->isInSubnet();
		}

		return $json;
	}

}
