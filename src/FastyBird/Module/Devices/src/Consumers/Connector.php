<?php declare(strict_types = 1);

/**
 * Connector.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Consumers
 * @since          0.1.0
 *
 * @date           22.10.22
 */

namespace FastyBird\Module\Devices\Consumers;

use FastyBird\Library\Exchange\Consumers as ExchangeConsumers;
use FastyBird\Library\Metadata;
use FastyBird\Library\Metadata\Entities as MetadataEntities;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\DataStorage as DevicesDataStorage;
use League\Flysystem;
use Nette\Utils;
use function strval;

/**
 * Devices connector subscriber
 *
 * @package         FastyBird:DevicesModule!
 * @subpackage      Consumers
 *
 * @author          Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Connector implements ExchangeConsumers\Consumer
{

	public function __construct(
		private readonly DevicesDataStorage\Reader $reader,
	)
	{
	}

	/**
	 * @throws Flysystem\FilesystemException
	 * @throws Utils\JsonException
	 */
	public function consume(
		MetadataTypes\TriggerSource|MetadataTypes\ModuleSource|MetadataTypes\PluginSource|MetadataTypes\ConnectorSource $source,
		MetadataTypes\RoutingKey $routingKey,
		MetadataEntities\Entity|null $entity,
	): void
	{
		if (
			Utils\Strings::startsWith(
				strval($routingKey->getValue()),
				Metadata\Constants::MESSAGE_BUS_ENTITY_PREFIX_KEY,
			)
			&& (
				Utils\Strings::contains(
					strval($routingKey->getValue()),
					Metadata\Constants::MESSAGE_BUS_ENTITY_CREATED_KEY,
				)
				|| Utils\Strings::contains(
					strval($routingKey->getValue()),
					Metadata\Constants::MESSAGE_BUS_ENTITY_UPDATED_KEY,
				)
				|| Utils\Strings::contains(
					strval($routingKey->getValue()),
					Metadata\Constants::MESSAGE_BUS_ENTITY_DELETED_KEY,
				)
			)
		) {
			$this->reader->read();
		}
	}

}
