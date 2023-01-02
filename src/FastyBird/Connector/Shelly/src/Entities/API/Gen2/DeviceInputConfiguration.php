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

use FastyBird\Connector\Shelly\Entities;
use FastyBird\Connector\Shelly\Types;
use Nette;

/**
 * Generation 2 device input configuration entity
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DeviceInputConfiguration implements Entities\API\Entity
{

	use Nette\SmartObject;

	public function __construct(
		private readonly int $id,
		private readonly string|null $name,
		private readonly string $type,
		private readonly bool $invert,
		private readonly bool $factoryReset,
		private readonly int|null $reportThr,
	)
	{
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function getType(): Types\ComponentType
	{
		return Types\ComponentType::get(Types\ComponentType::TYPE_INPUT);
	}

	public function getName(): string|null
	{
		return $this->name;
	}

	public function getInputType(): Types\InputType
	{
		if (Types\InputType::isValidValue($this->type)) {
			return Types\InputType::get($this->type);
		}

		return Types\InputType::get(Types\InputType::TYPE_SWITCH);
	}

	public function isInverted(): bool
	{
		return $this->invert;
	}

	public function hasFactoryReset(): bool
	{
		return $this->factoryReset;
	}

	public function getReportThreshold(): int|null
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
			'type' => $this->getType()->getValue(),
			'name' => $this->getName(),
			'input_type' => $this->getInputType()->getValue(),
			'inverted' => $this->isInverted(),
			'factory_reset' => $this->hasFactoryReset(),
			'report_threshold' => $this->getReportThreshold(),
		];
	}

}
