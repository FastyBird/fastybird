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
use Orisai\ObjectMapper;
use PHPOnCouch;
use function class_exists;

/**
 * State object factory
 *
 * @package        FastyBird:CouchDbPlugin!
 * @subpackage     States
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final readonly class StateFactory
{

	public function __construct(
		private ObjectMapper\Processing\Processor $stateMapper,
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
	 * @throws InvalidArgumentException
	 */
	public function create(string $class, PHPOnCouch\CouchDocument $document): State
	{
		if (!class_exists($class)) {
			throw new Exceptions\InvalidState('State could not be created');
		}

		$data = [];

		foreach ($document->getKeys() as $key) {
			$data[$key] = $document->get($key);
		}

		try {
			$options = new ObjectMapper\Processing\Options();
			$options->setAllowUnknownFields();

			return $this->stateMapper->process($data, $class, $options);
		} catch (ObjectMapper\Exception\InvalidData $ex) {
			$errorPrinter = new ObjectMapper\Printers\ErrorVisualPrinter(
				new ObjectMapper\Printers\TypeToStringConverter(),
			);

			throw new Exceptions\InvalidArgument('Could not map data to state: ' . $errorPrinter->printError($ex));
		}
	}

}
