<?php declare(strict_types = 1);

/**
 * StateFactory.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:CouchDbPlugin!
 * @subpackage     States
 * @since          1.0.0
 *
 * @date           03.03.20
 */

namespace FastyBird\Plugin\CouchDb\States;

use FastyBird\Plugin\CouchDb\Exceptions;
use FastyBird\Plugin\CouchDb\States;
use InvalidArgumentException;
use phpDocumentor;
use PHPOnCouch;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionParameter;
use ReflectionProperty;
use ReflectionType;
use Reflector;
use Throwable;
use function array_merge;
use function array_search;
use function boolval;
use function call_user_func_array;
use function class_exists;
use function floatval;
use function intval;
use function is_callable;
use function method_exists;
use function strtolower;
use function strval;
use function trim;
use function ucfirst;

/**
 * State object factory
 *
 * @package        FastyBird:CouchDbPlugin!
 * @subpackage     States
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class StateFactory
{

	/**
	 * @template T of States\State
	 *
	 * @phpstan-param class-string<T> $stateClass
	 *
	 * @phpstan-return T
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws InvalidArgumentException
	 */
	public static function create(string $stateClass, PHPOnCouch\CouchDocument $document): State
	{
		if (!class_exists($stateClass)) {
			throw new Exceptions\InvalidState('State could not be created');
		}

		try {
			$rc = new ReflectionClass($stateClass);

			$constructor = $rc->getConstructor();

			$state = $constructor !== null
				? $rc->newInstanceArgs(
					self::autowireArguments($constructor, $document),
				)
				: new $stateClass();
		} catch (Throwable $ex) {
			throw new Exceptions\InvalidState('State could not be created', 0, $ex);
		}

		$properties = self::getProperties($rc);

		foreach ($properties as $rp) {
			$varAnnotation = self::parseAnnotation($rp, 'var');

			if (array_search($rp->getName(), $document->getKeys(), true) !== false) {
				$value = $document->get($rp->getName());

				$methodName = 'set' . ucfirst($rp->getName());

				if ($varAnnotation === 'int') {
					$value = intval($value);
				} elseif ($varAnnotation === 'float') {
					$value = floatval($value);
				} elseif ($varAnnotation === 'bool') {
					$value = boolval($value);
				} elseif ($varAnnotation === 'string') {
					$value = strval($value);
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

		return $state;
	}

	/**
	 * This method was inspired by same method in Nette framework
	 *
	 * @return array<mixed>
	 *
	 * @throws InvalidArgumentException
	 * @throws ReflectionException
	 */
	private static function autowireArguments(
		ReflectionMethod $method,
		PHPOnCouch\CouchDocument $document,
	): array
	{
		$res = [];

		foreach ($method->getParameters() as $num => $parameter) {
			$parameterName = $parameter->getName();
			$parameterType = self::getParameterType($parameter);

			if (
				!$parameter->isVariadic()
				&& array_search($parameterName, $document->getKeys(), true) !== false
			) {
				$res[$num] = $document->get($parameterName);

			} elseif ($parameterName === 'id') {
				$res[$num] = $document->id();

			} elseif ($parameterName === 'document') {
				$res[$num] = $document;

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
	 * @return array<ReflectionProperty>
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
				return trim(strval($tag));
			}
		}

		return null;
	}

}
