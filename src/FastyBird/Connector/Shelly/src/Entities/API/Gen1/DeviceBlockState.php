<?php declare(strict_types = 1);

/**
 * DeviceBlockState.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           26.08.23
 */

namespace FastyBird\Connector\Shelly\Entities\API\Gen1;

use FastyBird\Connector\Shelly\Entities;

/**
 * Generation 1 device block state entity
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DeviceBlockState implements Entities\API\Entity
{

	public function __construct(
		private readonly int $block,
		private readonly int $sensor,
		private readonly int|float|string $value,
	)
	{
	}

	public function getBlock(): int
	{
		return $this->block;
	}

	public function getSensor(): int
	{
		return $this->sensor;
	}

	public function getValue(): float|int|string
	{
		return $this->value;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'block' => $this->getBlock(),
			'sensor' => $this->getSensor(),
			'value' => $this->getValue(),
		];
	}

}
