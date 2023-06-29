<?php declare(strict_types = 1);

/**
 * CreatedDevice.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VieraConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           28.06.23
 */

namespace FastyBird\Connector\Viera\Entities\Messages;

use Nette;
use Ramsey\Uuid;
use function array_map;

/**
 * Newly created device entity
 *
 * @package        FastyBird:VieraConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class CreatedDevice implements Entity
{

	use Nette\SmartObject;

	/**
	 * @param array<CreatedDeviceHdmi> $hdmi
	 * @param array<CreatedDeviceApplication> $applications
	 */
	public function __construct(
		private readonly Uuid\UuidInterface $connector,
		private readonly string $identifier,
		private readonly string $ipAddress,
		private readonly int $port,
		private readonly string|null $name,
		private readonly string|null $model,
		private readonly string|null $manufacturer,
		private readonly string|null $serialNumber,
		private readonly string|null $appId,
		private readonly string|null $encryptionKey,
		private readonly array $hdmi,
		private readonly array $applications,
	)
	{
	}

	public function getConnector(): Uuid\UuidInterface
	{
		return $this->connector;
	}

	public function getIdentifier(): string
	{
		return $this->identifier;
	}

	public function getIpAddress(): string
	{
		return $this->ipAddress;
	}

	public function getPort(): int
	{
		return $this->port;
	}

	public function getName(): string|null
	{
		return $this->name;
	}

	public function getModel(): string|null
	{
		return $this->model;
	}

	public function getManufacturer(): string|null
	{
		return $this->manufacturer;
	}

	public function getSerialNumber(): string|null
	{
		return $this->serialNumber;
	}

	public function getAppId(): string|null
	{
		return $this->appId;
	}

	public function getEncryptionKey(): string|null
	{
		return $this->encryptionKey;
	}

	/**
	 * @return array<CreatedDeviceHdmi>
	 */
	public function getHdmi(): array
	{
		return $this->hdmi;
	}

	/**
	 * @return array<CreatedDeviceApplication>
	 */
	public function getApplications(): array
	{
		return $this->applications;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'connector' => $this->getConnector(),
			'identifier' => $this->getIdentifier(),
			'ip_address' => $this->getIpAddress(),
			'port' => $this->getPort(),
			'name' => $this->getName(),
			'model' => $this->getModel(),
			'manufacturer' => $this->getManufacturer(),
			'serial_number' => $this->getSerialNumber(),
			'app_id' => $this->getAppId(),
			'encryption_key' => $this->getEncryptionKey(),
			'hdmi' => array_map(
				static fn (CreatedDeviceHdmi $item): array => $item->toArray(),
				$this->getHdmi(),
			),
			'applications' => array_map(
				static fn (CreatedDeviceApplication $item): array => $item->toArray(),
				$this->getApplications(),
			),
		];
	}

}
