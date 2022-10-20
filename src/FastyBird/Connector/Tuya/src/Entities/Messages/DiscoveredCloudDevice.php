<?php declare(strict_types = 1);

/**
 * DiscoveredCloudDevice.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Entities
 * @since          0.13.0
 *
 * @date           27.08.22
 */

namespace FastyBird\Connector\Tuya\Entities\Messages;

use FastyBird\Connector\Tuya\Types;
use Nette;
use Ramsey\Uuid;
use function array_map;

/**
 * Discovered cloud device entity
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DiscoveredCloudDevice implements Entity
{

	use Nette\SmartObject;

	/**
	 * @param Array<DiscoveredCloudDataPoint> $dataPoints
	 */
	public function __construct(
		private readonly Uuid\UuidInterface $connector,
		private readonly string $id,
		private readonly string $localKey,
		private readonly string|null $ipAddress,
		private readonly string|null $name,
		private readonly string|null $model,
		private readonly string|null $sn,
		private readonly string|null $mac,
		private readonly array $dataPoints,
		private readonly Types\MessageSource $source,
	)
	{
	}

	public function getConnector(): Uuid\UuidInterface
	{
		return $this->connector;
	}

	public function getId(): string
	{
		return $this->id;
	}

	public function getLocalKey(): string
	{
		return $this->localKey;
	}

	public function getIpAddress(): string|null
	{
		return $this->ipAddress;
	}

	public function getName(): string|null
	{
		return $this->name;
	}

	public function getModel(): string|null
	{
		return $this->model;
	}

	public function getSn(): string|null
	{
		return $this->sn;
	}

	public function getMac(): string|null
	{
		return $this->mac;
	}

	/**
	 * @return Array<DiscoveredCloudDataPoint>
	 */
	public function getDataPoints(): array
	{
		return $this->dataPoints;
	}

	public function getSource(): Types\MessageSource
	{
		return $this->source;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'connector' => $this->getConnector()->toString(),
			'id' => $this->getId(),
			'local_key' => $this->getLocalKey(),
			'ip_address' => $this->getIpAddress(),
			'name' => $this->getName(),
			'model' => $this->getModel(),
			'sn' => $this->getSn(),
			'mac' => $this->getMac(),
			'data_points' => array_map(
				static fn (DiscoveredCloudDataPoint $item): array => $item->toArray(),
				$this->getDataPoints(),
			),
			'source' => $this->getSource()->getValue(),
		];
	}

}
