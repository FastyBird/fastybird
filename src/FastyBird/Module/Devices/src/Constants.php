<?php declare(strict_types = 1);

/**
 * Constants.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     common
 * @since          1.0.0
 *
 * @date           18.03.20
 */

namespace FastyBird\Module\Devices;

use FastyBird\Library\Metadata;

/**
 * Service constants
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     common
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Constants
{

	/**
	 * Module routing
	 */

	public const ROUTE_NAME_DEVICES = 'devices';

	public const ROUTE_NAME_DEVICE = 'device';

	public const ROUTE_NAME_DEVICE_RELATIONSHIP = 'device.relationship';

	public const ROUTE_NAME_DEVICE_PARENTS = 'device.parents';

	public const ROUTE_NAME_DEVICE_CHILDREN = 'device.children';

	public const ROUTE_NAME_DEVICE_PROPERTIES = 'device.properties';

	public const ROUTE_NAME_DEVICE_PROPERTY = 'device.property';

	public const ROUTE_NAME_DEVICE_PROPERTY_RELATIONSHIP = 'device.property.relationship';

	public const ROUTE_NAME_DEVICE_PROPERTY_CHILDREN = 'device.property.children';

	public const ROUTE_NAME_DEVICE_PROPERTY_STATE = 'device.property.state';

	public const ROUTE_NAME_DEVICE_CONTROLS = 'device.controls';

	public const ROUTE_NAME_DEVICE_CONTROL = 'device.control';

	public const ROUTE_NAME_DEVICE_CONTROL_RELATIONSHIP = 'device.control.relationship';

	public const ROUTE_NAME_CHANNELS = 'channels';

	public const ROUTE_NAME_CHANNEL = 'channel';

	public const ROUTE_NAME_CHANNEL_RELATIONSHIP = 'channel.relationship';

	public const ROUTE_NAME_CHANNEL_PROPERTIES = 'channel.properties';

	public const ROUTE_NAME_CHANNEL_PROPERTY = 'channel.property';

	public const ROUTE_NAME_CHANNEL_PROPERTY_RELATIONSHIP = 'channel.property.relationship';

	public const ROUTE_NAME_CHANNEL_PROPERTY_CHILDREN = 'channel.property.children';

	public const ROUTE_NAME_CHANNEL_PROPERTY_STATE = 'channel.property.state';

	public const ROUTE_NAME_CHANNEL_CONTROLS = 'channel.controls';

	public const ROUTE_NAME_CHANNEL_CONTROL = 'channel.control';

	public const ROUTE_NAME_CHANNEL_CONTROL_RELATIONSHIP = 'channel.control.relationship';

	public const ROUTE_NAME_CONNECTORS = 'connectors';

	public const ROUTE_NAME_CONNECTOR = 'connector';

	public const ROUTE_NAME_CONNECTOR_RELATIONSHIP = 'connector.relationship';

	public const ROUTE_NAME_CONNECTOR_PROPERTIES = 'connector.properties';

	public const ROUTE_NAME_CONNECTOR_PROPERTY = 'connector.property';

	public const ROUTE_NAME_CONNECTOR_PROPERTY_RELATIONSHIP = 'connector.property.relationship';

	public const ROUTE_NAME_CONNECTOR_PROPERTY_STATE = 'connector.property.state';

	public const ROUTE_NAME_CONNECTOR_CONTROLS = 'connector.controls';

	public const ROUTE_NAME_CONNECTOR_CONTROL = 'connector.control';

	public const ROUTE_NAME_CONNECTOR_CONTROL_RELATIONSHIP = 'connector.control.relationship';

	/**
	 * Message bus routing keys mapping
	 */
	public const MESSAGE_BUS_CREATED_ENTITIES_ROUTING_KEYS_MAPPING
		= [
			Entities\Connectors\Connector::class => Metadata\Constants::MESSAGE_BUS_CONNECTOR_DOCUMENT_CREATED_ROUTING_KEY,
			Entities\Connectors\Properties\Property::class => Metadata\Constants::MESSAGE_BUS_CONNECTOR_PROPERTY_DOCUMENT_CREATED_ROUTING_KEY,
			Entities\Connectors\Controls\Control::class => Metadata\Constants::MESSAGE_BUS_CONNECTOR_CONTROL_DOCUMENT_CREATED_ROUTING_KEY,
			Entities\Devices\Device::class => Metadata\Constants::MESSAGE_BUS_DEVICE_DOCUMENT_CREATED_ROUTING_KEY,
			Entities\Devices\Properties\Property::class => Metadata\Constants::MESSAGE_BUS_DEVICE_PROPERTY_DOCUMENT_CREATED_ROUTING_KEY,
			Entities\Devices\Controls\Control::class => Metadata\Constants::MESSAGE_BUS_DEVICE_CONTROL_DOCUMENT_CREATED_ROUTING_KEY,
			Entities\Channels\Channel::class => Metadata\Constants::MESSAGE_BUS_CHANNEL_DOCUMENT_CREATED_ROUTING_KEY,
			Entities\Channels\Properties\Property::class => Metadata\Constants::MESSAGE_BUS_CHANNEL_PROPERTY_DOCUMENT_CREATED_ROUTING_KEY,
			Entities\Channels\Controls\Control::class => Metadata\Constants::MESSAGE_BUS_CHANNEL_CONTROL_DOCUMENT_CREATED_ROUTING_KEY,
		];

	public const MESSAGE_BUS_UPDATED_ENTITIES_ROUTING_KEYS_MAPPING
		= [
			Entities\Connectors\Connector::class => Metadata\Constants::MESSAGE_BUS_CONNECTOR_DOCUMENT_UPDATED_ROUTING_KEY,
			Entities\Connectors\Properties\Property::class => Metadata\Constants::MESSAGE_BUS_CONNECTOR_PROPERTY_DOCUMENT_UPDATED_ROUTING_KEY,
			Entities\Connectors\Controls\Control::class => Metadata\Constants::MESSAGE_BUS_CONNECTOR_CONTROL_DOCUMENT_UPDATED_ROUTING_KEY,
			Entities\Devices\Device::class => Metadata\Constants::MESSAGE_BUS_DEVICE_DOCUMENT_UPDATED_ROUTING_KEY,
			Entities\Devices\Properties\Property::class => Metadata\Constants::MESSAGE_BUS_DEVICE_PROPERTY_DOCUMENT_UPDATED_ROUTING_KEY,
			Entities\Devices\Controls\Control::class => Metadata\Constants::MESSAGE_BUS_DEVICE_CONTROL_DOCUMENT_UPDATED_ROUTING_KEY,
			Entities\Channels\Channel::class => Metadata\Constants::MESSAGE_BUS_CHANNEL_DOCUMENT_UPDATED_ROUTING_KEY,
			Entities\Channels\Properties\Property::class => Metadata\Constants::MESSAGE_BUS_CHANNEL_PROPERTY_DOCUMENT_UPDATED_ROUTING_KEY,
			Entities\Channels\Controls\Control::class => Metadata\Constants::MESSAGE_BUS_CHANNEL_CONTROL_DOCUMENT_UPDATED_ROUTING_KEY,
		];

	public const MESSAGE_BUS_DELETED_ENTITIES_ROUTING_KEYS_MAPPING
		= [
			Entities\Connectors\Connector::class => Metadata\Constants::MESSAGE_BUS_CONNECTOR_DOCUMENT_DELETED_ROUTING_KEY,
			Entities\Connectors\Properties\Property::class => Metadata\Constants::MESSAGE_BUS_CONNECTOR_PROPERTY_DOCUMENT_DELETED_ROUTING_KEY,
			Entities\Connectors\Controls\Control::class => Metadata\Constants::MESSAGE_BUS_CONNECTOR_CONTROL_DOCUMENT_DELETED_ROUTING_KEY,
			Entities\Devices\Device::class => Metadata\Constants::MESSAGE_BUS_DEVICE_DOCUMENT_DELETED_ROUTING_KEY,
			Entities\Devices\Properties\Property::class => Metadata\Constants::MESSAGE_BUS_DEVICE_PROPERTY_DOCUMENT_DELETED_ROUTING_KEY,
			Entities\Devices\Controls\Control::class => Metadata\Constants::MESSAGE_BUS_DEVICE_CONTROL_DOCUMENT_DELETED_ROUTING_KEY,
			Entities\Channels\Channel::class => Metadata\Constants::MESSAGE_BUS_CHANNEL_DOCUMENT_DELETED_ROUTING_KEY,
			Entities\Channels\Properties\Property::class => Metadata\Constants::MESSAGE_BUS_CHANNEL_PROPERTY_DOCUMENT_DELETED_ROUTING_KEY,
			Entities\Channels\Controls\Control::class => Metadata\Constants::MESSAGE_BUS_CHANNEL_CONTROL_DOCUMENT_DELETED_ROUTING_KEY,
		];

	public const CONFIGURATION_KEY = 'devices-module-data.json';

	public const DATA_STORAGE_PROPERTIES_KEY = 'properties';

	public const DATA_STORAGE_CONTROLS_KEY = 'controls';

	public const DATA_STORAGE_CONNECTORS_KEY = 'connectors';

	public const DATA_STORAGE_DEVICES_KEY = 'devices';

	public const DATA_STORAGE_CHANNELS_KEY = 'channels';

	public const EVENT_ENTITY_CREATED = 'entityCreated';

	public const EVENT_ENTITY_UPDATED = 'entityUpdated';

	public const EVENT_ENTITY_DELETED = 'entityDeleted';

	public const EVENT_TERMINATE = 'terminate';

	public const EVENT_RESTART = 'restart';

}
