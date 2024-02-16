<?php declare(strict_types = 1);

/**
 * ConsistenceEnumValue.php
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

use Attribute;
use Consistence\Enum\Enum;
use Orisai\ObjectMapper;

#[Attribute(Attribute::TARGET_PROPERTY)]
final readonly class ConsistenceEnumValue implements ObjectMapper\Rules\RuleDefinition
{

	/**
	 * @param class-string<Enum> $class
	 * @param array<mixed>|null $allowedValues
	 */
	public function __construct(
		private string $class,
		private bool $allowUnknown = false,
		private array|null $allowedValues = null,
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
			'allowedValues' => $this->allowedValues,
		];
	}

}
