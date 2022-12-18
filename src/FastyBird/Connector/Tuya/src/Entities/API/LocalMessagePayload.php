<?php declare(strict_types = 1);

/**
 * Sign.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Entities
 * @since          0.13.0
 *
 * @date           10.12.22
 */

namespace FastyBird\Connector\Tuya\Entities\API;

use FastyBird\Connector\Tuya\Types;
use Nette;

/**
 * Local API device message entity
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class LocalMessagePayload implements Entity
{

	use Nette\SmartObject;

	public function __construct(
		private readonly Types\LocalDeviceCommand $command,
		private readonly string|null $payload,
	)
	{
	}

	public function getCommand(): Types\LocalDeviceCommand
	{
		return $this->command;
	}

	public function getPayload(): string|null
	{
		return $this->payload;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'command' => $this->getCommand()->getValue(),
			'payload' => $this->getPayload(),
		];
	}

}
