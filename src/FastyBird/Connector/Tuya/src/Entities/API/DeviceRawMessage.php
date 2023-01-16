<?php declare(strict_types = 1);

/**
 * DeviceRawMessage.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           26.04.22
 */

namespace FastyBird\Connector\Tuya\Entities\API;

use FastyBird\Connector\Tuya\Types;
use Nette;
use function array_map;
use function is_array;

/**
 * Local api device raw message entity
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DeviceRawMessage implements Entity
{

	use Nette\SmartObject;

	/**
	 * @param string|Entity|array<Entity>|null $data
	 */
	public function __construct(
		private readonly string $identifier,
		private readonly Types\LocalDeviceCommand $command,
		private readonly int $sequence,
		private readonly int|null $returnCode,
		private readonly string|Entity|array|null $data = null,
		private readonly Types\LocalDeviceError|null $error = null,
	)
	{
	}

	public function getIdentifier(): string
	{
		return $this->identifier;
	}

	public function getCommand(): Types\LocalDeviceCommand
	{
		return $this->command;
	}

	public function getSequence(): int
	{
		return $this->sequence;
	}

	public function getReturnCode(): int|null
	{
		return $this->returnCode;
	}

	/**
	 * @return string|Entity|array<Entity>|null
	 */
	public function getData(): string|Entity|array|null
	{
		return $this->data;
	}

	public function getError(): Types\LocalDeviceError|null
	{
		return $this->error;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'identifier' => $this->getIdentifier(),
			'command' => $this->getCommand()->getValue(),
			'sequence' => $this->getSequence(),
			'return_code' => $this->getReturnCode(),
			'data' => $this->getData() instanceof Entity ? $this->getData()->toArray() : (is_array(
				$this->getData(),
			) ? array_map(
				static fn (
					Entity $entity,
				): array => $entity->toArray(),
				$this->getData(),
			) : $this->getData()),
			'error' => $this->getError()?->getValue(),
		];
	}

}
