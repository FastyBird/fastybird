<?php declare(strict_types = 1);

/**
 * ConsistenceEnumRule.php
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
use Orisai\Exceptions\Logic\InvalidArgument;
use Orisai\ObjectMapper;
use function in_array;
use function is_string;
use function is_subclass_of;

/**
 * @implements ObjectMapper\Rules\Rule<ConsistenceEnumArgs>
 */
final class ConsistenceEnumRule implements ObjectMapper\Rules\Rule
{

	private const
		ClassName = 'class',
		AllowUnknown = 'allowUnknown',
		AllowedValues = 'allowedValues';

	/**
	 * @throws InvalidArgument
	 */
	public function resolveArgs(array $args, ObjectMapper\Context\ArgsContext $context): ConsistenceEnumArgs
	{
		$checker = new ObjectMapper\Args\ArgsChecker($args, self::class);
		$checker->checkAllowedArgs([self::ClassName, self::AllowUnknown, self::AllowedValues]);

		$checker->checkRequiredArg(self::ClassName);
		$class = $args[self::ClassName];

		if (!is_string($class) || !is_subclass_of($class, Enum::class)) {
			throw InvalidArgument::create()
				->withMessage($checker->formatMessage(
					Enum::class,
					self::ClassName,
					$class,
				));
		}

		$allowUnknown = false;
		if ($checker->hasArg(self::AllowUnknown)) {
			$allowUnknown = $checker->checkBool(self::AllowUnknown);
		}

		$allowedValues = null;
		if ($checker->hasArg(self::AllowedValues)) {
			$allowedValues = $checker->checkNullableArray(self::AllowedValues);
		}

		return new ConsistenceEnumArgs($class, $allowUnknown, $allowedValues);
	}

	public function getArgsType(): string
	{
		return ConsistenceEnumArgs::class;
	}

	/**
	 * @param ConsistenceEnumArgs $args
	 *
	 * @throws ObjectMapper\Exception\ValueDoesNotMatch
	 */
	public function processValue(
		mixed $value,
		ObjectMapper\Args\Args $args,
		ObjectMapper\Context\FieldContext $context,
	): Enum|null
	{
		$class = $args->class;

		if ($value instanceof Enum && $value::class === $class) {
			return $value;
		}

		if (
			$args->allowUnknown
			&& (
				$value === null
				|| !$class::isValidValue($value)
			)
		) {
			return null;
		}

		if ($value === null || !$class::isValidValue($value)) {
			throw ObjectMapper\Exception\ValueDoesNotMatch::create(
				$this->createType($args, $context),
				ObjectMapper\Processing\Value::of($value),
			);
		}

		$value = $class::get($value);

		if (
			$args->allowedValues !== null
			&& !in_array($value->getValue(), $args->allowedValues, true)
		) {
			throw ObjectMapper\Exception\ValueDoesNotMatch::create(
				$this->createType($args, $context),
				ObjectMapper\Processing\Value::of($value),
			);
		}

		return $value;
	}

	/**
	 * @param ConsistenceEnumArgs $args
	 */
	public function createType(
		ObjectMapper\Args\Args $args,
		ObjectMapper\Context\TypeContext $context,
	): ObjectMapper\Types\EnumType
	{
		$class = $args->class;

		return new ObjectMapper\Types\EnumType((array) $class::getAvailableValues());
	}

}