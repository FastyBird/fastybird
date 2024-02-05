<?php declare(strict_types = 1);

/**
 * MessageBuilder.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VirtualConnector!
 * @subpackage     Helpers
 * @since          1.0.0
 *
 * @date           17.10.23
 */

namespace FastyBird\Connector\Virtual\Queue;

use FastyBird\Connector\Virtual\Exceptions;
use FastyBird\Connector\Virtual\Queue;
use Orisai\ObjectMapper;

/**
 * Entity helper
 *
 * @package        FastyBird:VirtualConnector!
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
	 * @return T
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

			throw new Exceptions\Runtime('Could not map data to entity: ' . $errorPrinter->printError($ex));
		}
	}

}
