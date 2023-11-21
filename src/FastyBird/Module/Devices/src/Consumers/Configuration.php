<?php declare(strict_types = 1);

/**
 * Configuration.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Consumers
 * @since          1.0.0
 *
 * @date           22.10.22
 */

namespace FastyBird\Module\Devices\Consumers;

use FastyBird\Library\Exchange\Consumers as ExchangeConsumers;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\Models;
use function in_array;

/**
 * Configuration messages subscriber
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Consumers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Configuration implements ExchangeConsumers\Consumer
{

	private const MODULE_ROUTING_KEYS = [
		MetadataTypes\RoutingKey::DEVICE_DOCUMENT_CREATED,
		MetadataTypes\RoutingKey::DEVICE_DOCUMENT_UPDATED,
		MetadataTypes\RoutingKey::DEVICE_DOCUMENT_DELETED,
		MetadataTypes\RoutingKey::DEVICE_PROPERTY_DOCUMENT_CREATED,
		MetadataTypes\RoutingKey::DEVICE_PROPERTY_DOCUMENT_UPDATED,
		MetadataTypes\RoutingKey::DEVICE_PROPERTY_DOCUMENT_DELETED,
		MetadataTypes\RoutingKey::DEVICE_CONTROL_DOCUMENT_CREATED,
		MetadataTypes\RoutingKey::DEVICE_CONTROL_DOCUMENT_UPDATED,
		MetadataTypes\RoutingKey::DEVICE_CONTROL_DOCUMENT_DELETED,
		MetadataTypes\RoutingKey::CHANNEL_DOCUMENT_CREATED,
		MetadataTypes\RoutingKey::CHANNEL_DOCUMENT_UPDATED,
		MetadataTypes\RoutingKey::CHANNEL_DOCUMENT_DELETED,
		MetadataTypes\RoutingKey::CHANNEL_PROPERTY_DOCUMENT_CREATED,
		MetadataTypes\RoutingKey::CHANNEL_PROPERTY_DOCUMENT_UPDATED,
		MetadataTypes\RoutingKey::CHANNEL_PROPERTY_DOCUMENT_DELETED,
		MetadataTypes\RoutingKey::CHANNEL_CONTROL_DOCUMENT_CREATED,
		MetadataTypes\RoutingKey::CHANNEL_CONTROL_DOCUMENT_UPDATED,
		MetadataTypes\RoutingKey::CHANNEL_CONTROL_DOCUMENT_DELETED,
		MetadataTypes\RoutingKey::CONNECTOR_DOCUMENT_CREATED,
		MetadataTypes\RoutingKey::CONNECTOR_DOCUMENT_UPDATED,
		MetadataTypes\RoutingKey::CONNECTOR_DOCUMENT_DELETED,
		MetadataTypes\RoutingKey::CONNECTOR_PROPERTY_DOCUMENT_CREATED,
		MetadataTypes\RoutingKey::CONNECTOR_PROPERTY_DOCUMENT_UPDATED,
		MetadataTypes\RoutingKey::CONNECTOR_PROPERTY_DOCUMENT_DELETED,
		MetadataTypes\RoutingKey::CONNECTOR_CONTROL_DOCUMENT_CREATED,
		MetadataTypes\RoutingKey::CONNECTOR_CONTROL_DOCUMENT_UPDATED,
		MetadataTypes\RoutingKey::CONNECTOR_CONTROL_DOCUMENT_DELETED,
	];

	public function __construct(
		private readonly Models\Configuration\Builder $configurationBuilder,
	)
	{
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function consume(
		MetadataTypes\AutomatorSource|MetadataTypes\ModuleSource|MetadataTypes\PluginSource|MetadataTypes\ConnectorSource $source,
		MetadataTypes\RoutingKey $routingKey,
		MetadataDocuments\Document|null $entity,
	): void
	{
		if ($entity === null) {
			return;
		}

		if (in_array($routingKey->getValue(), self::MODULE_ROUTING_KEYS, true)) {
			$this->configurationBuilder->build();
		}
	}

}
