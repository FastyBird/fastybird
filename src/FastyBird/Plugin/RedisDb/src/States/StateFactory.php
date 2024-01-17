<?php declare(strict_types = 1);

/**
 * StateFactory.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RedisDbPlugin!
 * @subpackage     States
 * @since          1.0.0
 *
 * @date           03.03.20
 */

namespace FastyBird\Plugin\RedisDb\States;

use FastyBird\Plugin\RedisDb\Exceptions;
use FastyBird\Plugin\RedisDb\States;
use Nette\Utils;
use Orisai\ObjectMapper;
use function array_map;
use function class_exists;
use function explode;
use function implode;
use function is_array;
use function lcfirst;

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

	public function __construct(
		private readonly ObjectMapper\Processing\Processor $stateMapper,
	)
	{
	}

	/**
	 * @template T of State
	 *
	 * @param class-string<T> $class
	 *
	 * @return T
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 */
	public function create(string $class, string $raw): State
	{
		if (!class_exists($class)) {
			throw new Exceptions\InvalidState('State could not be created');
		}

		try {
			$decoded = Utils\Json::decode($raw, Utils\Json::FORCE_ARRAY);

			if (!is_array($decoded)) {
				throw new Exceptions\InvalidArgument('Provided state content is not valid JSON');
			}
		} catch (Utils\JsonException $ex) {
			throw new Exceptions\InvalidArgument('Provided state content is not valid JSON', $ex->getCode(), $ex);
		}

		try {
			$options = new ObjectMapper\Processing\Options();
			$options->setAllowUnknownFields();

			return $this->stateMapper->process($decoded, $class, $options);
		} catch (ObjectMapper\Exception\InvalidData $ex) {
			$errorPrinter = new ObjectMapper\Printers\ErrorVisualPrinter(
				new ObjectMapper\Printers\TypeToStringConverter(),
			);

			throw new Exceptions\InvalidArgument('Could not map data to state: ' . $errorPrinter->printError($ex));
		}
	}

}
