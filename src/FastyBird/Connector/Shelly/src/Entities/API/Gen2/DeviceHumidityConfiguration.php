<?php declare(strict_types = 1);

/**
 * DeviceHumidityConfiguration.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           26.12.22
 */

namespace FastyBird\Connector\Shelly\Entities\API\Gen2;

use FastyBird\Connector\Shelly\Entities;
use FastyBird\Connector\Shelly\Types;

/**
 * Generation 2 device humidity configuration entity
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DeviceHumidityConfiguration implements Entities\API\Entity
{

	public function __construct(
		private readonly int $id,
		private readonly string|null $name,
		private readonly float|null $reportThreshold,
		private readonly float|null $offset,
	)
	{
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function getType(): Types\ComponentType
	{
		return Types\ComponentType::get(Types\ComponentType::HUMIDITY);
	}

	public function getName(): string|null
	{
		return $this->name;
	}

	public function getReportThreshold(): float|null
	{
		return $this->reportThreshold;
	}

	public function getOffset(): float|null
	{
		return $this->offset;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'id' => $this->getId(),
			'type' => $this->getType()->getValue(),
			'name' => $this->getName(),
			'report_threshold' => $this->getReportThreshold(),
			'offset' => $this->getOffset(),
		];
	}

}
