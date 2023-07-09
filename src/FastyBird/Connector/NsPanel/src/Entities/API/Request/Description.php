<?php declare(strict_types = 1);

/**
 * Description.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           09.07.23
 */

namespace FastyBird\Connector\NsPanel\Entities\API\Request;

use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Connector\NsPanel\Types;
use Nette;
use stdClass;
use function array_map;

/**
 * Device description
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Description implements Entities\API\Entity
{

	use Nette\SmartObject;

	/**
	 * @param array<Capability> $capabilities
	 * @param array<Entities\API\Request\Capability\State> $state
	 * @param array<string, string> $tags
	 */
	public function __construct(
		private readonly string $thirdSerialNumber,
		private readonly string $name,
		private readonly Types\DeviceType $displayCategory,
		private readonly array $capabilities,
		private readonly array $state,
		private readonly array $tags,
		private readonly string $manufacturer,
		private readonly string $model,
		private readonly string $firmwareVersion,
		private readonly string $serviceAddress,
	)
	{
	}

	public function getThirdSerialNumber(): string
	{
		return $this->thirdSerialNumber;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getDisplayCategory(): Types\DeviceType
	{
		return $this->displayCategory;
	}

	/**
	 * @return array<Capability>
	 */
	public function getCapabilities(): array
	{
		return $this->capabilities;
	}

	/**
	 * @return array<Entities\API\Request\Capability\State>
	 */
	public function getState(): array
	{
		return $this->state;
	}

	/**
	 * @return array<string, string>
	 */
	public function getTags(): array
	{
		return $this->tags;
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

	public function getServiceAddress(): string
	{
		return $this->serviceAddress;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'third_serial_number' => $this->getThirdSerialNumber(),
			'name' => $this->getName(),
			'display_category' => $this->getDisplayCategory()->getValue(),
			'capabilities' => array_map(
				static fn (Capability $capability): array => $capability->toArray(),
				$this->getCapabilities(),
			),
			'state' => array_map(
				static fn (Entities\API\Request\Capability\State $state): array => $state->toArray(),
				$this->getState(),
			),
			'tags' => $this->getTags(),
			'manufacturer' => $this->getManufacturer(),
			'model' => $this->getModel(),
			'firmware_version' => $this->getFirmwareVersion(),
			'service_address' => $this->getServiceAddress(),
		];
	}

	public function toJson(): object
	{
		$json = new stdClass();
		$json->third_serial_number = $this->getThirdSerialNumber();
		$json->name = $this->getName();
		$json->display_category = $this->getDisplayCategory()->getValue();
		$json->capabilities = array_map(
			static fn (Capability $capability): object => $capability->toJson(),
			$this->getCapabilities(),
		);
		$json->state = array_map(
			static fn (Entities\API\Request\Capability\State $state): object => $state->toJson(),
			$this->getState(),
		);
		$json->tags = $this->getTags();
		$json->manufacturer = $this->getManufacturer();
		$json->model = $this->getModel();
		$json->firmware_version = $this->getFirmwareVersion();
		$json->service_address = $this->getServiceAddress();

		return $json;
	}

}
