<?php declare(strict_types = 1);

/**
 * Entity.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:HomeKitConnector!
 * @subpackage     Helpers
 * @since          1.0.0
 *
 * @date           30.11.23
 */

namespace FastyBird\Connector\HomeKit\Queue;

use FastyBird\Connector\HomeKit\Exceptions;
use FastyBird\Connector\HomeKit\Queue;
use Orisai\ObjectMapper;

/**
 * Entity helper
 *
 * @package        FastyBird:HomeKitConnector!
 * @subpackage     Helpers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class MessageBuilder
{

	public function __construct(
		private readonly ObjectMapper\Processing\Processor $entityMapper,
	)
	{
	}

	/**
	 * @template T of Queue\Messages\Message
	 *
	 * @param class-string<T> $entity
	 * @param array<mixed> $data
	 *
	 * @throws Exceptions\Runtime
	 */
	public function create(string $entity, array $data): Queue\Messages\Message
	{
		try {
			$options = new ObjectMapper\Processing\Options();
			$options->setAllowUnknownFields();

			return $this->entityMapper->process($data, $entity, $options);
		} catch (ObjectMapper\Exception\InvalidData $ex) {
			$errorPrinter = new ObjectMapper\Printers\ErrorVisualPrinter(
				new ObjectMapper\Printers\TypeToStringConverter(),
			);

			throw new Exceptions\Runtime('Could not map data to message: ' . $errorPrinter->printError($ex));
		}
	}

}
