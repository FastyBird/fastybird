<?php declare(strict_types = 1);

/**
 * EntityFactory.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           06.05.23
 */

namespace FastyBird\Connector\NsPanel\Entities;

use Consistence;
use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Connector\NsPanel\Exceptions;
use Nette\Utils;
use phpDocumentor;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionProperty;
use ReflectionUnionType;
use Reflector;
use stdClass;
use Throwable;
use function array_combine;
use function array_filter;
use function array_keys;
use function array_merge;
use function assert;
use function call_user_func_array;
use function class_exists;
use function class_implements;
use function explode;
use function get_declared_classes;
use function get_object_vars;
use function in_array;
use function interface_exists;
use function is_array;
use function is_bool;
use function is_callable;
use function is_float;
use function is_int;
use function is_string;
use function is_subclass_of;
use function ltrim;
use function preg_replace_callback;
use function property_exists;
use function sprintf;
use function strtolower;
use function strtoupper;
use function strval;
use function trim;
use function ucfirst;

/**
 * Entity factory
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class EntityFactory
{

	private const TYPE_STRING = 'string';

	private const TYPE_INT = 'int';

	private const TYPE_FLOAT = 'float';

	private const TYPE_BOOL = 'bool';

	/**
	 * @param class-string<T> $entityClass
	 *
	 * @template T of Entities\Entity
	 *
	 * @return T
	 *
	 * @throws Exceptions\InvalidState
	 */
	public static function build(
		string $entityClass,
		Utils\ArrayHash $data,
	): Entities\Entity
	{
		if (!class_exists($entityClass)) {
			throw new Exceptions\InvalidState('Transformer could not be created. Class could not be found');
		}

		$decoded = self::convertKeys($data);
		$decoded = self::convertToObject($decoded);

		try {
			$rc = new ReflectionClass($entityClass);

			$constructor = $rc->getConstructor();

			$entity = $constructor !== null
				? $rc->newInstanceArgs(
					self::autowireArguments($rc->getNamespaceName(), $constructor, $decoded),
				)
				: new $entityClass();
		} catch (Throwable $ex) {
			throw new Exceptions\InvalidState('Transformer could not be created: ' . $ex->getMessage(), 0, $ex);
		}

		$properties = self::getProperties($rc);

		foreach ($properties as $rp) {
			$varAnnotation = self::parseVarAnnotation($rp);

			if (
				in_array($rp->getName(), array_keys(get_object_vars($decoded)), true) === true
				&& property_exists($decoded, $rp->getName())
			) {
				$value = $decoded->{$rp->getName()};

				$methodName = 'set' . ucfirst($rp->getName());

				if ($varAnnotation === self::TYPE_INT) {
					$value = (int) $value;
				} elseif ($varAnnotation === self::TYPE_FLOAT) {
					$value = (float) $value;
				} elseif ($varAnnotation === self::TYPE_BOOL) {
					$value = (bool) $value;
				} elseif ($varAnnotation === self::TYPE_STRING) {
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
					throw new Exceptions\InvalidState('Transformer could not be created: ' . $ex->getMessage(), 0, $ex);
				}
			}
		}

		return $entity;
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function convertKeys(Utils\ArrayHash $data): array
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
	 * @return array<int, mixed>
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws ReflectionException
	 */
	private static function autowireArguments(
		string $namespace,
		ReflectionMethod $method,
		stdClass $decoded,
	): array
	{
		$res = [];

		foreach ($method->getParameters() as $num => $parameter) {
			$parameterName = $parameter->getName();
			$parameterTypes = self::getParameterTypes($parameter);

			if (
				!$parameter->isVariadic()
				&& in_array($parameterName, array_keys(get_object_vars($decoded)), true) === true
			) {
				$parameterValue = $decoded->{$parameterName};

				foreach ($parameterTypes as $parameterType) {
					if (
						!class_exists($parameterType)
						&& class_exists($namespace . '\\' . $parameterType)
					) {
						$parameterType = $namespace . '\\' . $parameterType;
					}

					if ($parameterType === 'array' && is_string($method->getDocComment())) {
						$factory = phpDocumentor\Reflection\DocBlockFactory::createInstance();

						$docBlock = $factory->create(
							$method->getDocComment(),
							new phpDocumentor\Reflection\Types\Context('\FastyBird\Connector\NsPanel'),
						);

						foreach ($docBlock->getTags() as $tag) {
							if (!$tag instanceof phpDocumentor\Reflection\DocBlock\Tags\Param) {
								continue;
							}

							$tagType = $tag->getType();

							if (
								$tag->getVariableName() === $parameterName
								&& $tagType instanceof phpDocumentor\Reflection\Types\Array_
							) {
								if ($parameterValue instanceof Utils\ArrayHash) {
									$arrayTypes = explode('|', strval($tagType->getValueType()));

									$subRes = [];

									foreach ($arrayTypes as $arrayType) {
										if (interface_exists($arrayType)) {
											$subclasses = self::getInterfaceClasses($arrayType);

											if ($subclasses !== []) {
												foreach ($parameterValue as $subParameterValue) {
													if ($subParameterValue instanceof Utils\ArrayHash) {
														assert(is_subclass_of($arrayType, Entities\Entity::class));

														$subEntity = null;

														foreach ($subclasses as $subclass) {
															try {
																$subEntity = self::build(
																	$subclass,
																	$subParameterValue,
																);

																break;
															} catch (Exceptions\InvalidState) {
																// Just ignore error if builder crash
															}
														}

														if ($subEntity !== null) {
															$subRes[] = $subEntity;
														}
													}
												}
											}
										} elseif (class_exists($arrayType)) {
											foreach ($parameterValue as $subParameterValue) {
												if ($subParameterValue instanceof Utils\ArrayHash) {
													assert(is_subclass_of($arrayType, Entities\Entity::class));

													$subRes[] = self::build($arrayType, $subParameterValue);
												}
											}
										} elseif (in_array(
											Utils\Strings::lower($arrayType),
											[self::TYPE_STRING, self::TYPE_INT, self::TYPE_FLOAT, self::TYPE_BOOL],
											true,
										)) {
											$arrayValues = array_filter(
												(array) $parameterValue,
												static fn ($item): bool => match (Utils\Strings::lower($arrayType)) {
														self::TYPE_STRING => is_string($item),
														self::TYPE_INT => is_int($item),
														self::TYPE_FLOAT => is_float($item),
														self::TYPE_BOOL => is_bool($item),
														default => false,
												},
											);

											if ($arrayValues !== []) {
												$subRes = $arrayValues;
											}
										} elseif (Utils\Strings::startsWith(
											Utils\Strings::lower($arrayType),
											'array<',
										)) {
											foreach ($parameterValue as $subParameterKey => $subParameterValue) {
												if ($subParameterValue instanceof Utils\ArrayHash) {
													$subRes[$subParameterKey] = (array) $subParameterValue;
												}
											}
										}
									}

									$res[$num] = $subRes;
								}
							}
						}

						break;
					} elseif (
						class_exists($parameterType)
						&& is_subclass_of($parameterType, Entities\Entity::class)
						&& (
							$parameterValue instanceof Utils\ArrayHash
							|| is_array($parameterValue)
						)
					) {
						if (interface_exists($parameterType)) {
							$subclasses = self::getInterfaceClasses($parameterType);

							$subEntity = null;

							foreach ($subclasses as $subclass) {
								try {
									$parameterValue = is_array($parameterValue)
										? Utils\ArrayHash::from($parameterValue)
										: $parameterValue;

									$subEntity = self::build($subclass, $parameterValue);

									break;
								} catch (Exceptions\InvalidState) {
									// Just ignore error if builder crash
								}
							}

							if ($subEntity !== null) {
								$res[$num] = $subEntity;
							}
						} elseif (class_exists($parameterType)) {
							$parameterValue = is_array($parameterValue)
								? Utils\ArrayHash::from($parameterValue)
								: $parameterValue;

							$res[$num] = self::build($parameterType, $parameterValue);
						}

						break;
					} elseif (
						class_exists($parameterType)
						&& is_subclass_of($parameterType, Consistence\Enum\Enum::class)
						&& is_string($parameterValue)
						&& $parameterType::isValidValue($parameterValue)
					) {
						if (!$parameterType::isValidValue($parameterValue)) {
							throw new Exceptions\InvalidArgument(
								sprintf(
									'Provided enum value %s is not valid for: %s',
									$parameterValue,
									$parameterType,
								),
							);
						}

						$res[$num] = $parameterType::get($parameterValue);

						break;
					}

					$res[$num] = $parameterValue;
				}
			} elseif ($parameterName === 'id' && property_exists($decoded, 'id')) {
				$res[$num] = $decoded->id;

			} elseif (
				(
					$parameterTypes !== []
					&& $parameter->allowsNull()
				)
				|| $parameter->isOptional()
				|| $parameter->isDefaultValueAvailable()
			) {
				// !optional + defaultAvailable = func($a = NULL, $b) since 5.4.7
				// optional + !defaultAvailable = i.e. Exception::__construct, mysqli::mysqli, ...
				$res[$num] = $parameter->isDefaultValueAvailable() ? $parameter->getDefaultValue() : null;
			} else {
				throw new Exceptions\InvalidArgument(
					sprintf(
						'Entity parameter is required but could not be created: %s',
						$parameterName,
					),
				);
			}
		}

		return $res;
	}

	/**
	 * @return array<string>
	 */
	private static function getParameterTypes(ReflectionParameter $param): array
	{
		if ($param->hasType()) {
			$rt = $param->getType();

			if ($rt instanceof ReflectionNamedType) {
				$type = $rt->getName();

				return [strtolower(
					$type,
				) === 'self' && $param->getDeclaringClass() !== null ? $param->getDeclaringClass()
					->getName() : $type];
			} elseif ($rt instanceof ReflectionUnionType) {
				$types = [];

				foreach ($rt->getTypes() as $subType) {
					if ($subType instanceof ReflectionNamedType) {
						$type = $subType->getName();

						$types[] = strtolower(
							$type,
						) === 'self' && $param->getDeclaringClass() !== null ? $param->getDeclaringClass()
							->getName() : $type;
					}
				}

				return $types;
			}
		}

		return [];
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

	private static function parseVarAnnotation(ReflectionProperty $rp): string|null
	{
		if ($rp->getDocComment() === false) {
			return null;
		}

		$factory = phpDocumentor\Reflection\DocBlockFactory::createInstance();
		$docblock = $factory->create($rp->getDocComment());

		foreach ($docblock->getTags() as $tag) {
			if ($tag->getName() === 'var') {
				return trim((string) $tag);
			}
		}

		return null;
	}

	/**
	 * @param array<string, mixed> $array
	 */
	private static function convertToObject(array $array): stdClass
	{
		$converted = new stdClass();

		foreach ($array as $key => $value) {
			$converted->{$key} = $value;
		}

		return $converted;
	}

	/**
	 * @param class-string $interface
	 *
	 * @return array<class-string<Entities\Entity>>
	 */
	private static function getInterfaceClasses(string $interface): array
	{
		return array_filter(
			get_declared_classes(),
			static fn ($className) =>
				in_array(ltrim($interface, '\\'), class_implements($className), true)
				&& is_subclass_of($className, Entities\Entity::class),
		);
	}

}
