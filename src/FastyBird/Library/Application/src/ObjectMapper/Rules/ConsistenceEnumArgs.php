<?php declare(strict_types = 1);

/**
 * ConsistenceEnumArgs.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Application!
 * @subpackage     ObjectMapper
 * @since          1.0.0
 *
 * @date           02.08.23
 */

namespace FastyBird\Library\Application\ObjectMapper\Rules;

use Consistence\Enum\Enum;
use Orisai\ObjectMapper\Args\Args;

final class ConsistenceEnumArgs implements Args
{

	/**
	 * @param class-string<Enum> $class
	 * @param array<mixed>|null $allowedValues
	 */
	public function __construct(
		public string $class,
		public bool $allowUnknown,
		public array|null $allowedValues,
	)
	{
	}

}
