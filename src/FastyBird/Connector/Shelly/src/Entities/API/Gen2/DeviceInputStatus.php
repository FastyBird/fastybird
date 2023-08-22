<?php declare(strict_types = 1);

/**
 * DeviceInputStatus.php
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
use function is_string;

/**
 * Generation 2 device input status entity
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DeviceInputStatus implements Entities\API\Entity
{

	use Nette\SmartObject;

	/**
	 * @param array<string> $errors
	 */
	public function __construct(
		private readonly int $id,
		private readonly string|bool|null $state,
		private readonly int|null $percent,
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
		return Types\ComponentType::get(Types\ComponentType::INPUT);
	}

	public function getState(): bool|Types\InputPayload|null
	{
		if (is_string($this->state)) {
			if (Types\InputPayload::isValidValue($this->state)) {
				return Types\InputPayload::get($this->state);
			}

			return null;
		}

		return $this->state;
	}

	public function getPercent(): int|null
	{
		return $this->percent;
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
			'state' => $this->getState() instanceof Types\InputPayload ? $this->getState()->getValue() : $this->getState(),
			'percent' => $this->getPercent(),
			'errors' => $this->getErrors(),
		];
	}

}
