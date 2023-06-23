<?php declare(strict_types = 1);

/**
 * Device.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VieraConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           21.06.23
 */

namespace FastyBird\Connector\Viera\Entities\API\Specs;

use FastyBird\Connector\Viera\Entities;
use FastyBird\Connector\Viera\Exceptions;
use function property_exists;
use function sprintf;
use function strval;
use function substr;

/**
 * Device specs device info entity
 *
 * @package        FastyBird:VieraConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Device implements Entities\API\Entity
{

	private string|null $deviceType = null;

	private string|null $friendlyName = null;

	private string|null $manufacturer = null;

	private string|null $modelName = null;

	private string|null $modelNumber = null;

	private bool $requiresEncryption = false;

	private string|null $UDN = null;

	public function getDeviceType(): string|null
	{
		return $this->deviceType;
	}

	public function getFriendlyName(): string|null
	{
		return $this->friendlyName;
	}

	public function getManufacturer(): string|null
	{
		return $this->manufacturer;
	}

	public function getModelName(): string|null
	{
		return $this->modelName;
	}

	public function getModelNumber(): string|null
	{
		return $this->modelNumber;
	}

	public function setRequiresEncryption(bool $requiresEncryption): void
	{
		$this->requiresEncryption = $requiresEncryption;
	}

	public function isRequiresEncryption(): bool
	{
		return $this->requiresEncryption;
	}

	public function getSerialNumber(): string|null
	{
		return $this->UDN;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'device_type' => $this->getDeviceType(),
			'friendly_name' => $this->getFriendlyName(),
			'manufacturer' => $this->getManufacturer(),
			'model_name' => $this->getModelName(),
			'model_number' => $this->getModelNumber(),
			'requires_encryption' => $this->isRequiresEncryption(),
			'serial_number' => $this->getSerialNumber(),
		];
	}

	public function __set(string $name, mixed $value): void
	{
		if (property_exists($this, $name)) {
			// @phpstan-ignore-next-line
			$this->{$name} = $value;
		}

		if ($name === 'UDN') {
			$this->UDN = substr(strval($value), 5);
		}
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 */
	public function __get(string $name): mixed
	{
		if (property_exists($this, $name)) {
			// @phpstan-ignore-next-line
			return $this->{$name};
		}

		throw new Exceptions\InvalidArgument(sprintf('Property %s does not exists on class %s', $name, self::class));
	}

}
