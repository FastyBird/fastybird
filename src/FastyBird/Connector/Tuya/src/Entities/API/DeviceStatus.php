<?php declare(strict_types = 1);

/**
 * DeviceStatus.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           16.11.23
 */

namespace FastyBird\Connector\Tuya\Entities\API;

use Nette;
use function array_map;

/**
 * Device status message entity
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DeviceStatus implements Entity
{

	use Nette\SmartObject;

	/**
	 * @param array<DataPointStatus> $dataPoints
	 */
	public function __construct(
		private readonly string $identifier,
		private readonly array $dataPoints,
	)
	{
	}

	public function getIdentifier(): string
	{
		return $this->identifier;
	}

	/**
	 * @return array<DataPointStatus>
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
			'identifier' => $this->getIdentifier(),
			'data_points' => array_map(
				static fn (DataPointStatus $channel): array => $channel->toArray(),
				$this->getDataPoints(),
			),
		];
	}

}
