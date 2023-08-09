<?php declare(strict_types = 1);

/**
 * ConsistenceEnumValue.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Bootstrap!
 * @subpackage     ObjectMapper
 * @since          1.0.0
 *
 * @date           02.08.23
 */

namespace FastyBird\Library\Bootstrap\ObjectMapper\Rules;

use Attribute;
use Consistence\Enum\Enum;
use Orisai\ObjectMapper;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class ConsistenceEnumValue implements ObjectMapper\Rules\RuleDefinition
{

	/**
	 * @param class-string<Enum> $class
	 */
	public function __construct(
		private readonly string $class,
		private readonly bool $allowUnknown = false,
	)
	{
	}

	public function getType(): string
	{
		return ConsistenceEnumRule::class;
	}

	public function getArgs(): array
	{
		return [
			'class' => $this->class,
			'allowUnknown' => $this->allowUnknown,
		];
	}

}
