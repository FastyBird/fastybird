<?php declare(strict_types = 1);

/**
 * OpenApiRequestSign.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     ValueObjects
 * @since          1.0.0
 *
 * @date           26.04.22
 */

namespace FastyBird\Connector\Tuya\ValueObjects;

use Orisai\ObjectMapper;

/**
 * OpenAPI sign entity
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     ValueObjects
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class OpenApiRequestSign implements ObjectMapper\MappedObject
{

	public function __construct(
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $sign,
		#[ObjectMapper\Rules\IntValue(unsigned: true)]
		private readonly int $timestamp,
	)
	{
	}

	public function getSign(): string
	{
		return $this->sign;
	}

	public function getTimestamp(): int
	{
		return $this->timestamp;
	}

}
