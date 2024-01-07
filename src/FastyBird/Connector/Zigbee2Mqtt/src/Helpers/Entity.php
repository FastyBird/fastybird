<?php declare(strict_types = 1);

/**
 * Entity.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Helpers
 * @since          1.0.0
 *
 * @date           23.12.23
 */

namespace FastyBird\Connector\Zigbee2Mqtt\Helpers;

use FastyBird\Connector\Zigbee2Mqtt\Entities;
use FastyBird\Connector\Zigbee2Mqtt\Exceptions;
use Orisai\ObjectMapper;

/**
 * Entity helper
 *
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Helpers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Entity
{

	public function __construct(
		private readonly ObjectMapper\Processing\Processor $entityMapper,
	)
	{
	}

	/**
	 * @template T of Entities\Messages\Entity
	 *
	 * @param class-string<T> $entity
	 * @param array<mixed> $data
	 *
	 * @return T
	 *
	 * @throws Exceptions\Runtime
	 */
	public function create(
		string $entity,
		array $data,
	): Entities\Messages\Entity
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
