<?php declare(strict_types = 1);

/**
 * DeviceState.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           21.12.23
 */

namespace FastyBird\Connector\Shelly\Entities\API\Gen2;

use FastyBird\Connector\Shelly\Entities;
use FastyBird\Connector\Shelly\Types;
use Orisai\ObjectMapper;

/**
 * Generation 2 device state entity
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
abstract class DeviceState implements Entities\API\Entity
{

	/**
	 * @param array<string> $errors
	 */
	public function __construct(
		#[ObjectMapper\Rules\IntValue(unsigned: true)]
		private readonly int $id = 0,
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\IntValue(unsigned: true),
		)]
		private readonly array $errors = [],
	)
	{
	}

	public function getId(): int
	{
		return $this->id;
	}

	abstract public function getType(): Types\ComponentType;

	/**
	 * @return array<string>
	 */
	public function getErrors(): array
	{
		return $this->errors;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'id' => $this->getId(),
			'type' => $this->getType()->getValue(),
			'errors' => $this->getErrors(),
		];
	}

	/**
	 * @return array<string, mixed>
	 */
	public function toState(): array
	{
		return [];
	}

}
