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

use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Connector\NsPanel\Types;
use Nette;
use function array_map;

/**
 * NS Panel sub-device description - both NS Panel connected & third-party
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DiscoveredSubDevice implements Entities\Clients\Entity
{

	use Nette\SmartObject;

	/**
	 * @param array<Entities\Clients\CapabilityDescription> $capabilities
	 * @param array<string, string|array<string, string>> $tags
	 */
	public function __construct(
		private readonly string $serial_number,
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
		private readonly array $tags = [],
		private readonly bool $online = false,
		private readonly bool|null $subnet = null,
	)
	{
	}

	public function getSerialNumber(): string
	{
		return $this->serial_number;
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
	 * @return array<Entities\Clients\CapabilityDescription>
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
				static fn (Entities\Clients\CapabilityDescription $capability): array => $capability->toArray(),
				$this->getCapabilities(),
			),
			'protocol' => $this->getProtocol(),
			'tags' => $this->getTags(),
			'online' => $this->isOnline(),
			'subnet' => $this->isInSubnet(),
		];
	}

}
