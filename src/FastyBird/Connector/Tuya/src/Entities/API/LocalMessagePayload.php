<?php declare(strict_types = 1);

/**
 * LocalMessagePayload.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           10.12.22
 */

namespace FastyBird\Connector\Tuya\Entities\API;

use FastyBird\Connector\Tuya\Types;
use FastyBird\Library\Bootstrap\ObjectMapper as BootstrapObjectMapper;
use Orisai\ObjectMapper;

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

	public function __construct(
		#[BootstrapObjectMapper\Rules\ConsistenceEnumValue(class: Types\LocalDeviceCommand::class)]
		private readonly Types\LocalDeviceCommand $command,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
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
