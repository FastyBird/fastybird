<?php declare(strict_types = 1);

/**
 * DiscoveredLocalDevice.php
 *
 * @license        More in license.md
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
 * Discovered local device entity
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DiscoveredLocalDevice implements Entity
{

	use Nette\SmartObject;

	/**
	 * @param Array<DiscoveredLocalDataPoint> $dataPoints
	 */
	public function __construct(
		private readonly Uuid\UuidInterface $connector,
		private readonly string $id,
		private readonly string $ipAddress,
		private readonly string $localKey,
		private readonly bool $encrypted,
		private readonly string $version,
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

	public function getIpAddress(): string
	{
		return $this->ipAddress;
	}

	public function getLocalKey(): string
	{
		return $this->localKey;
	}

	public function isEncrypted(): bool
	{
		return $this->encrypted;
	}

	public function getVersion(): string
	{
		return $this->version;
	}

	public function getSource(): Types\MessageSource
	{
		return $this->source;
	}

	/**
	 * @return Array<DiscoveredLocalDataPoint>
	 */
	public function getDataPoints(): array
	{
		return $this->dataPoints;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'connector' => $this->getConnector()->toString(),
			'id' => $this->id,
			'ip_address' => $this->ipAddress,
			'local_key' => $this->localKey,
			'encrypted' => $this->encrypted,
			'version' => $this->version,
			'data_points' => array_map(
				static fn (DiscoveredLocalDataPoint $item): array => $item->toArray(),
				$this->getDataPoints(),
			),
			'source' => $this->getSource()->getValue(),
		];
	}

}
