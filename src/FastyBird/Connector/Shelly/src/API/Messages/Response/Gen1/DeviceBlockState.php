<?php declare(strict_types = 1);

/**
 * DeviceBlockState.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     API
 * @since          1.0.0
 *
 * @date           26.08.23
 */

namespace FastyBird\Connector\Shelly\API\Messages\Response\Gen1;

use FastyBird\Connector\Shelly\API;
use Orisai\ObjectMapper;

/**
 * Generation 1 device block state message
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     API
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DeviceBlockState implements API\Messages\Message
{

	public function __construct(
		#[ObjectMapper\Rules\IntValue()]
		private readonly int $block,
		#[ObjectMapper\Rules\IntValue()]
		private readonly int $sensor,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\IntValue(),
			new ObjectMapper\Rules\FloatValue(),
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private readonly int|float|string|null $value,
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

	public function getValue(): float|int|string|null
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
