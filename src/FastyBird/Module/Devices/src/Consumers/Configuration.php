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
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices;
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
		Devices\Constants::MESSAGE_BUS_DEVICE_DOCUMENT_CREATED_ROUTING_KEY,
		Devices\Constants::MESSAGE_BUS_DEVICE_DOCUMENT_UPDATED_ROUTING_KEY,
		Devices\Constants::MESSAGE_BUS_DEVICE_DOCUMENT_DELETED_ROUTING_KEY,
		Devices\Constants::MESSAGE_BUS_DEVICE_PROPERTY_DOCUMENT_CREATED_ROUTING_KEY,
		Devices\Constants::MESSAGE_BUS_DEVICE_PROPERTY_DOCUMENT_UPDATED_ROUTING_KEY,
		Devices\Constants::MESSAGE_BUS_DEVICE_PROPERTY_DOCUMENT_DELETED_ROUTING_KEY,
		Devices\Constants::MESSAGE_BUS_DEVICE_CONTROL_DOCUMENT_CREATED_ROUTING_KEY,
		Devices\Constants::MESSAGE_BUS_DEVICE_CONTROL_DOCUMENT_UPDATED_ROUTING_KEY,
		Devices\Constants::MESSAGE_BUS_DEVICE_CONTROL_DOCUMENT_DELETED_ROUTING_KEY,
		Devices\Constants::MESSAGE_BUS_CHANNEL_DOCUMENT_CREATED_ROUTING_KEY,
		Devices\Constants::MESSAGE_BUS_CHANNEL_DOCUMENT_UPDATED_ROUTING_KEY,
		Devices\Constants::MESSAGE_BUS_CHANNEL_DOCUMENT_DELETED_ROUTING_KEY,
		Devices\Constants::MESSAGE_BUS_CHANNEL_PROPERTY_DOCUMENT_CREATED_ROUTING_KEY,
		Devices\Constants::MESSAGE_BUS_CHANNEL_PROPERTY_DOCUMENT_UPDATED_ROUTING_KEY,
		Devices\Constants::MESSAGE_BUS_CHANNEL_PROPERTY_DOCUMENT_DELETED_ROUTING_KEY,
		Devices\Constants::MESSAGE_BUS_CHANNEL_CONTROL_DOCUMENT_CREATED_ROUTING_KEY,
		Devices\Constants::MESSAGE_BUS_CHANNEL_CONTROL_DOCUMENT_UPDATED_ROUTING_KEY,
		Devices\Constants::MESSAGE_BUS_CHANNEL_CONTROL_DOCUMENT_DELETED_ROUTING_KEY,
		Devices\Constants::MESSAGE_BUS_CONNECTOR_DOCUMENT_CREATED_ROUTING_KEY,
		Devices\Constants::MESSAGE_BUS_CONNECTOR_DOCUMENT_UPDATED_ROUTING_KEY,
		Devices\Constants::MESSAGE_BUS_CONNECTOR_DOCUMENT_DELETED_ROUTING_KEY,
		Devices\Constants::MESSAGE_BUS_CONNECTOR_PROPERTY_DOCUMENT_CREATED_ROUTING_KEY,
		Devices\Constants::MESSAGE_BUS_CONNECTOR_PROPERTY_DOCUMENT_UPDATED_ROUTING_KEY,
		Devices\Constants::MESSAGE_BUS_CONNECTOR_PROPERTY_DOCUMENT_DELETED_ROUTING_KEY,
		Devices\Constants::MESSAGE_BUS_CONNECTOR_CONTROL_DOCUMENT_CREATED_ROUTING_KEY,
		Devices\Constants::MESSAGE_BUS_CONNECTOR_CONTROL_DOCUMENT_UPDATED_ROUTING_KEY,
		Devices\Constants::MESSAGE_BUS_CONNECTOR_CONTROL_DOCUMENT_DELETED_ROUTING_KEY,
	];

	public function __construct(
		private readonly Models\Configuration\Builder $configurationBuilder,
	)
	{
	}

	public function consume(
		MetadataTypes\Sources\Source $source,
		string $routingKey,
		MetadataDocuments\Document|null $document,
	): void
	{
		if ($document === null) {
			return;
		}

		if (in_array($routingKey, self::MODULE_ROUTING_KEYS, true)) {
			$this->configurationBuilder->clean();
		}
	}

}
