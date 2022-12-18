<?php declare(strict_types = 1);

/**
 * EntityFactory.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Entities
 * @since          0.57.0
 *
 * @date           02.06.22
 */

namespace FastyBird\Library\Metadata\Entities;

use FastyBird\Library\Metadata\Exceptions;
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
use function array_combine;
use function array_keys;
use function array_merge;
use function call_user_func_array;
use function class_exists;
use function get_object_vars;
use function in_array;
use function is_array;
use function is_callable;
use function method_exists;
use function preg_replace_callback;
use function property_exists;
use function strtolower;
use function strtoupper;
use function trim;
use function ucfirst;

/**
 * Data entity factory
 *
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
abstract class EntityFactory
{

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 */
	protected function build(string $entityClass, Utils\ArrayHash $data): Entity
	{
		if (!class_exists($entityClass)) {
			throw new Exceptions\InvalidState('Entity could not be created');
		}

		try {
			$decoded = $this->convertKeys($data);
			$decoded = Utils\Json::decode(Utils\Json::encode($decoded), Utils\Json::FORCE_ARRAY);

			if (is_array($decoded)) {
				$decoded = $this->convertToObject($decoded);
			}
		} catch (Utils\JsonException $ex) {
			throw new Exceptions\InvalidArgument('Provided entity content is not valid JSON', $ex->getCode(), $ex);
		}

		if (!$decoded instanceof stdClass) {
			throw new Exceptions\InvalidState('Data for entity could not be prepared');
		}

		try {
			$rc = new ReflectionClass($entityClass);

			$constructor = $rc->getConstructor();

			$entity = $constructor !== null
				? $rc->newInstanceArgs(
					$this->autowireArguments($constructor, $decoded),
				)
				: new $entityClass();
		} catch (Throwable $ex) {
			throw new Exceptions\InvalidState('Entity could not be created', 0, $ex);
		}

		$properties = $this->getProperties($rc);

		foreach ($properties as $rp) {
			$varAnnotation = $this->parseAnnotation($rp, 'var');

			if (
				in_array($rp->getName(), array_keys(get_object_vars($decoded)), true) === true
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
					$rm = new ReflectionMethod($entityClass, $methodName);

					if ($rm->isPublic()) {
						$callback = [$entity, $methodName];

						// Try to call entity setter
						if (is_callable($callback)) {
							call_user_func_array($callback, [$value]);
						}
					}
				} catch (ReflectionException) {
					continue;
				} catch (Throwable $ex) {
					throw new Exceptions\InvalidState('Entity could not be created', 0, $ex);
				}
			}
		}

		if ($entity instanceof Entity) {
			return $entity;
		}

		throw new Exceptions\InvalidState('Entity could not be created');
	}

	/**
	 * @return array<string, mixed>
	 */
	protected function convertKeys(Utils\ArrayHash $data): array
	{
		$keys = preg_replace_callback(
			'/_(.)/',
			static fn (array $m): string => strtoupper($m[1]),
			array_keys((array) $data),
		);

		if ($keys === null) {
			return [];
		}

		return array_combine($keys, (array) $data);
	}

	/**
	 * This method was inspired by same method in Nette framework
	 *
	 * @return array<mixed>
	 *
	 * @throws ReflectionException
	 */
	private function autowireArguments(
		ReflectionMethod $method,
		stdClass $decoded,
	): array
	{
		$res = [];

		foreach ($method->getParameters() as $num => $parameter) {
			$parameterName = $parameter->getName();
			$parameterType = $this->getParameterType($parameter);

			if (
				!$parameter->isVariadic()
				&& in_array($parameterName, array_keys(get_object_vars($decoded)), true) === true
			) {
				$res[$num] = $decoded->{$parameterName};

			} elseif ($parameterName === 'id' && property_exists($decoded, 'id')) {
				$res[$num] = $decoded->id;

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
	private function getParameterType(ReflectionParameter $param): string|null
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
	private function getProperties(Reflector $rc): array
	{
		if (!$rc instanceof ReflectionClass) {
			return [];
		}

		$properties = [];

		foreach ($rc->getProperties() as $rcProperty) {
			$properties[] = $rcProperty;
		}

		if ($rc->getParentClass() !== false) {
			$properties = array_merge($properties, $this->getProperties($rc->getParentClass()));
		}

		return $properties;
	}

	/**
	 * @phpstan-return string|NULL
	 */
	private function parseAnnotation(ReflectionProperty $rp, string $name): string|null
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

	/**
	 * @param array<string, mixed> $array
	 */
	private function convertToObject(array $array): stdClass
	{
		$converted = new stdClass();

		foreach ($array as $key => $value) {
			$converted->{$key} = $value;
		}

		return $converted;
	}

}
