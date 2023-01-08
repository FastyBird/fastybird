<?php declare(strict_types = 1);

/**
 * DeviceHumidityStatus.php
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
use Nette\Utils;

/**
 * Generation 2 device humidity status entity
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DeviceHumidityStatus implements Entities\API\Entity
{

	use Nette\SmartObject;

	/**
	 * @param array<string> $errors
	 */
	public function __construct(
		private readonly int $id,
		private readonly float|null $rh,
		private readonly array|Utils\ArrayHash $errors = [],
	)
	{
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function getType(): Types\ComponentType
	{
		return Types\ComponentType::get(Types\ComponentType::TYPE_HUMIDITY);
	}

	public function getRelativeHumidity(): float|null
	{
		return $this->rh;
	}

	/**
	 * @return array<string>
	 */
	public function getErrors(): array
	{
		return $this->errors instanceof Utils\ArrayHash ? (array) $this->errors : $this->errors;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'id' => $this->getId(),
			'type' => $this->getType()->getValue(),
			'relative_humidity' => $this->getRelativeHumidity(),
			'errors' => $this->getErrors(),
		];
	}

}
