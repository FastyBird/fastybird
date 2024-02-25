<?php declare(strict_types = 1);

/**
 * LocalMessagePayload.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     API
 * @since          1.0.0
 *
 * @date           10.12.22
 */

namespace FastyBird\Connector\Tuya\API\Messages\Response;

use FastyBird\Connector\Tuya\API;
use FastyBird\Connector\Tuya\Types;
use Orisai\ObjectMapper;

/**
 * Local API device message
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     API
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final readonly class LocalMessagePayload implements API\Messages\Message
{

	public function __construct(
		#[ObjectMapper\Rules\BackedEnumValue(class: Types\LocalDeviceCommand::class)]
		private Types\LocalDeviceCommand $command,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private string|null $payload,
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
			'command' => $this->getCommand()->value,
			'payload' => $this->getPayload(),
		];
	}

}
