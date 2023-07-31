<?php declare(strict_types = 1);

/**
 * ThirdPartyDevice.php
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

namespace FastyBird\Connector\NsPanel\Entities\API;

use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Connector\NsPanel\Types;
use Nette;
use stdClass;
use function array_map;
use function is_array;

/**
 * Third party device description definition
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ThirdPartyDevice implements Entities\API\Entity
{

	use Nette\SmartObject;

	/**
	 * @param array<Entities\API\Capability> $capabilities
	 * @param array<Entities\API\Statuses\Status> $state
	 * @param array<string, string|array<string, string>> $tags
	 */
	public function __construct(
		private readonly string $thirdSerialNumber,
		private readonly string $name,
		private readonly Types\Category $displayCategory,
		private readonly array $capabilities,
		private readonly array $state,
		private readonly array $tags,
		private readonly string $manufacturer,
		private readonly string $model,
		private readonly string $firmwareVersion,
		private readonly string $serviceAddress,
		private readonly bool $online = false,
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

	public function getDisplayCategory(): Types\Category
	{
		return $this->displayCategory;
	}

	/**
	 * @return array<Entities\API\Capability>
	 */
	public function getCapabilities(): array
	{
		return $this->capabilities;
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

	public function isOnline(): bool
	{
		return $this->online;
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
				static fn (Entities\API\Capability $capability): array => $capability->toArray(),
				$this->getCapabilities(),
			),
			'state' => array_map(
				static fn (Entities\API\Statuses\Status $state): array => $state->toArray(),
				$this->getStatuses(),
			),
			'tags' => $this->getTags(),
			'manufacturer' => $this->getManufacturer(),
			'model' => $this->getModel(),
			'firmware_version' => $this->getFirmwareVersion(),
			'service_address' => $this->getServiceAddress(),
			'online' => $this->isOnline(),
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
		$json->third_serial_number = $this->getThirdSerialNumber();
		$json->name = $this->getName();
		$json->display_category = $this->getDisplayCategory()->getValue();
		$json->capabilities = array_map(
			static fn (Entities\API\Capability $capability): object => $capability->toJson(),
			$this->getCapabilities(),
		);
		$json->state = $state;
		$json->tags = $tags;
		$json->manufacturer = $this->getManufacturer();
		$json->model = $this->getModel();
		$json->firmware_version = $this->getFirmwareVersion();
		$json->service_address = $this->getServiceAddress();
		$json->online = $this->isOnline();

		return $json;
	}

}
