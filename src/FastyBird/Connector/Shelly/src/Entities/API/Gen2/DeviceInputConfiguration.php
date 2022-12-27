<?php declare(strict_types = 1);

/**
 * DeviceInputConfiguration.php
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

use FastyBird\Connector\Shelly\Entities\API\Entity;
use Nette;

/**
 * Generation 2 device input configuration entity
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DeviceInputConfiguration implements Entity
{

	use Nette\SmartObject;

	public function __construct(
		private readonly int $id,
		private readonly string|null $name,
		private readonly string $type,
		private readonly bool $invert,
		private readonly bool $factoryReset,
		private readonly int $reportThr,
	)
	{
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function getName(): string|null
	{
		return $this->name;
	}

	public function getType(): string
	{
		return $this->type;
	}

	public function isInverted(): bool
	{
		return $this->invert;
	}

	public function hasFactoryReset(): bool
	{
		return $this->factoryReset;
	}

	public function getReportThreshold(): int
	{
		return $this->reportThr;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'id' => $this->getId(),
			'name' => $this->getName(),
			'type' => $this->getType(),
			'inverted' => $this->isInverted(),
			'factory_reset' => $this->hasFactoryReset(),
			'report_thereshold' => $this->getReportThreshold(),
		];
	}

}
