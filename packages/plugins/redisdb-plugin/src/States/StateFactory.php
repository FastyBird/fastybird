<?php declare(strict_types = 1);

/**
 * StateFactory.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RedisDbPlugin!
 * @subpackage     States
 * @since          0.1.0
 *
 * @date           03.03.20
 */

namespace FastyBird\RedisDbPlugin\States;

use FastyBird\RedisDbPlugin\Exceptions;
use Nette\Utils;
use phpDocumentor;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionParameter;
use ReflectionProperty;
use ReflectionType;
use Reflector;
use stdClass;
use Throwable;
use function array_keys;
use function array_map;
use function array_merge;
use function array_search;
use function call_user_func_array;
use function class_exists;
use function explode;
use function get_object_vars;
use function implode;
use function is_array;
use function is_callable;
use function lcfirst;
use function method_exists;
use function property_exists;
use function strtolower;
use function trim;
use function ucfirst;

/**
 * State object factory
 *
 * @package        FastyBird:RedisDbPlugin!
 * @subpackage     States
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class StateFactory
{

	public static function create(string $stateClass, string $raw): State
	{
		if (!class_exists($stateClass)) {
			throw new Exceptions\InvalidState('State could not be created');
		}

		try {
			$decoded = Utils\Json::decode($raw, Utils\Json::FORCE_ARRAY);

			if (!is_array($decoded)) {
				throw new Exceptions\InvalidArgument('Provided state content is not valid JSON');
			}

			$decoded = self::convertKeys($decoded);

			$decoded = Utils\Json::decode(Utils\Json::encode($decoded));

		} catch (Utils\JsonException $ex) {
			throw new Exceptions\InvalidArgument('Provided state content is not valid JSON', $ex->getCode(), $ex);
		}

		if (!$decoded instanceof stdClass) {
			throw new Exceptions\InvalidArgument('Provided state content is not valid JSON');
		}

		try {
			$rc = new ReflectionClass($stateClass);

			$constructor = $rc->getConstructor();

			$state = $constructor !== null
				? $rc->newInstanceArgs(
					self::autowireArguments($constructor, $decoded, $raw),
				)
				: new $stateClass();
		} catch (Throwable $ex) {
			throw new Exceptions\InvalidState('State could not be created', 0, $ex);
		}

		$properties = self::getProperties($rc);

		foreach ($properties as $rp) {
			$varAnnotation = self::parseAnnotation($rp, 'var');

			if (
				array_search($rp->getName(), array_keys(get_object_vars($decoded)), true) !== false
				&& property_exists($decoded, $rp->getName())
			) {
				$value = $decoded->{$rp->getName()};

				$methodName = 'set' . ucfirst($rp->getName());

				if ($varAnnotation === 'int') {
					$value = (int) $value;
				} elseif ($varAnnotation === 'float') {
					$value = (float) $value;
				} elseif ($varAnnotation === 'bool') {
					$value = (bool) $value;
				} elseif ($varAnnotation === 'string') {
					$value = (string) $value;
				}

				try {
					$rm = new ReflectionMethod($stateClass, $methodName);

					if ($rm->isPublic()) {
						$callback = [$state, $methodName];

						// Try to call state setter
						if (is_callable($callback)) {
							call_user_func_array($callback, [$value]);
						}
					}
				} catch (ReflectionException) {
					continue;
				} catch (Throwable $ex) {
					throw new Exceptions\InvalidState('State could not be created', 0, $ex);
				}
			}
		}

		if ($state instanceof State) {
			return $state;
		}

		throw new Exceptions\InvalidState('State could not be created');
	}

	/**
	 * @param Array<mixed> $xs
	 *
	 * @return Array<mixed>
	 */
	private static function convertKeys(array $xs): array
	{
		$out = [];

		foreach ($xs as $key => $value) {
			$out[lcfirst(implode('', array_map('ucfirst', explode('_', $key))))] = is_array($value)
				? self::convertKeys($value)
				: $value;
		}

		return $out;
	}

	/**
	 * This method was inspired with same method in Nette framework
	 *
	 * @return Array<mixed>
	 */
	private static function autowireArguments(
		ReflectionMethod $method,
		stdClass $decoded,
		string $raw,
	): array
	{
		$res = [];

		foreach ($method->getParameters() as $num => $parameter) {
			$parameterName = $parameter->getName();
			$parameterType = self::getParameterType($parameter);

			if (
				!$parameter->isVariadic()
				&& array_search($parameterName, array_keys(get_object_vars($decoded)), true) !== false
			) {
				$res[$num] = $decoded->{$parameterName};

			} elseif ($parameterName === 'id' && property_exists($decoded, 'id')) {
				$res[$num] = $decoded->id;

			} elseif ($parameterName === 'raw') {
				$res[$num] = $raw;

			} elseif (
				(
					$parameterType !== null
					&& $parameter->allowsNull()
				)
				|| $parameter->isOptional()
				|| $parameter->isDefaultValueAvailable()
			) {
				// !optional + defaultAvailable = func($a = NULL, $b) since 5.4.7
				// optional + !defaultAvailable = i.e. Exception::__construct, mysqli::mysqli, ...
				$res[$num] = $parameter->isDefaultValueAvailable() ? $parameter->getDefaultValue() : null;
			}
		}

		return $res;
	}

	/**
	 * @phpstan-return string|NULL
	 */
	private static function getParameterType(ReflectionParameter $param): string|null
	{
		if ($param->hasType()) {
			$rt = $param->getType();

			if ($rt instanceof ReflectionType && method_exists($rt, 'getName')) {
				$type = $rt->getName();

				return strtolower(
					$type,
				) === 'self' && $param->getDeclaringClass() !== null ? $param->getDeclaringClass()
					->getName() : $type;
			}
		}

		return null;
	}

	/**
	 * @return Array<ReflectionProperty>
	 */
	private static function getProperties(Reflector $rc): array
	{
		if (!$rc instanceof ReflectionClass) {
			return [];
		}

		$properties = [];

		foreach ($rc->getProperties() as $rcProperty) {
			$properties[] = $rcProperty;
		}

		if ($rc->getParentClass() !== false) {
			$properties = array_merge($properties, self::getProperties($rc->getParentClass()));
		}

		return $properties;
	}

	/**
	 * @phpstan-return string|NULL
	 */
	private static function parseAnnotation(ReflectionProperty $rp, string $name): string|null
	{
		if ($rp->getDocComment() === false) {
			return null;
		}

		$factory = phpDocumentor\Reflection\DocBlockFactory::createInstance();
		$docblock = $factory->create($rp->getDocComment());

		foreach ($docblock->getTags() as $tag) {
			if ($tag->getName() === $name) {
				return trim((string) $tag);
			}
		}

		return null;
	}

}
