<?php declare(strict_types = 1);

/**
 * Constants.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     common
 * @since          0.1.0
 *
 * @date           04.05.20
 */

namespace FastyBird\Library\Metadata;

use const DIRECTORY_SEPARATOR as DS;

/**
 * Library constants
 *
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     common
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Constants
{

	public const RESOURCES_FOLDER = __DIR__ . '/../resources';

	public const EXCHANGE_CHANNEL_NAME = 'fb_exchange';

	/**
	 * App sources
	 */

	public const NOT_SPECIFIED_SOURCE = '*';

	public const MODULE_ACCOUNTS_SOURCE = 'com.fastybird.accounts-module';

	public const MODULE_DEVICES_SOURCE = 'com.fastybird.devices-module';

	public const MODULE_TRIGGERS_SOURCE = 'com.fastybird.triggers-module';

	public const MODULE_UI_SOURCE = 'com.fastybird.ui-module';

	public const MODULE_WEB_UI_SOURCE = 'com.fastybird.web-ui-module';

	public const PLUGIN_STORAGE_COUCHDB_SOURCE = 'com.fastybird.couchdb-storage-plugin';

	public const PLUGIN_RABBITMQ_SOURCE = 'com.fastybird.rabbitmq-plugin';

	public const PLUGIN_REDISDB_SOURCE = 'com.fastybird.redisdb-plugin';

	public const PLUGIN_WS_EXCHANGE_SOURCE = 'com.fastybird.ws-exchange-plugin';

	public const PLUGIN_WEB_SERVER_SOURCE = 'com.fastybird.web-server-plugin';

	public const PLUGIN_API_KEY = 'com.fastybird.api-key-plugin';

	public const CONNECTOR_FB_BUS_SOURCE = 'com.fastybird.fb-bus-connector';

	public const CONNECTOR_FB_MQTT_SOURCE = 'com.fastybird.fb-mqtt-connector';

	public const CONNECTOR_SHELLY_SOURCE = 'com.fastybird.shelly-connector';

	public const CONNECTOR_TUYA_SOURCE = 'com.fastybird.tuya-connector';

	public const CONNECTOR_SONOFF_SOURCE = 'com.fastybird.sonoff-connector';

	public const CONNECTOR_MODBUS_SOURCE = 'com.fastybird.modbus-connector';

	public const CONNECTOR_HOMEKIT_SOURCE = 'com.fastybird.homekit-connector';

	public const CONNECTOR_ITEAD_SOURCE = 'com.fastybird.itead-connector';

	public const CONNECTOR_VIRTUAL_SOURCE = 'com.fastybird.virtual-connector';

	public const CONNECTOR_TERMINAL_SOURCE = 'com.fastybird.terminal-connector';

	public const AUTOMATOR_DEVICE_MODULE = 'com.fastybird.device-module-automator';

	public const AUTOMATOR_DATE_TIME = 'com.fastybird.date-time-automator';

	public const BRIDGE_REDISDB_DEVICES_MODULE = 'com.fastybird.redisdb-devices-module-bridge';

	public const BRIDGE_REDISDB_TRIGGERS_MODULE = 'com.fastybird.redisdb-triggers-module-bridge';

	public const BRIDGE_REDISDB_WS_EXCHANGE = 'com.fastybird.redisdb-ws-exchange-bridge';

	public const BRIDGE_WS_EXCHANGE_DEVICES_MODULE = 'com.fastybird.ws-exchange-devices-module-bridge';

	/**
	 * Modules prefixes
	 */

	public const MODULE_ACCOUNTS_PREFIX = 'accounts-module';

	public const MODULE_DEVICES_PREFIX = 'devices-module';

	public const MODULE_TRIGGERS_PREFIX = 'triggers-module';

	public const MODULE_UI_PREFIX = 'ui-module';

	/**
	 * Message bus routing keys
	 */

	/**
	 * Global
	 */

	public const MESSAGE_BUS_CONNECTOR_CONTROL_ACTION_ROUTING_KEY = 'fb.exchange.action.connector.control';

	public const MESSAGE_BUS_CONNECTOR_PROPERTY_ACTION_ROUTING_KEY = 'fb.exchange.action.connector.property';

	public const MESSAGE_BUS_DEVICE_CONTROL_ACTION_ROUTING_KEY = 'fb.exchange.action.device.control';

	public const MESSAGE_BUS_DEVICE_PROPERTY_ACTION_ROUTING_KEY = 'fb.exchange.action.device.property';

	public const MESSAGE_BUS_CHANNEL_CONTROL_ACTION_ROUTING_KEY = 'fb.exchange.action.channel.control';

	public const MESSAGE_BUS_CHANNEL_PROPERTY_ACTION_ROUTING_KEY = 'fb.exchange.action.channel.property';

	public const MESSAGE_BUS_TRIGGER_CONTROL_ACTION_ROUTING_KEY = 'fb.exchange.action.trigger.control';

	public const MESSAGE_BUS_MODULE_MESSAGE_ROUTING_KEY = 'fb.exchange.message.module';

	public const MESSAGE_BUS_PLUGIN_MESSAGE_ROUTING_KEY = 'fb.exchange.message.plugin';

	public const MESSAGE_BUS_CONNECTOR_MESSAGE_ROUTING_KEY = 'fb.exchange.message.connector';

	public const MESSAGE_BUS_TRIGGER_MESSAGE_ROUTING_KEY = 'fb.exchange.message.trigger';

	public const MESSAGE_BUS_ENTITY_PREFIX_KEY = 'fb.exchange.module.entity';

	public const MESSAGE_BUS_ENTITY_REPORTED_KEY = 'reported';

	public const MESSAGE_BUS_ENTITY_CREATED_KEY = 'created';

	public const MESSAGE_BUS_ENTITY_UPDATED_KEY = 'updated';

	public const MESSAGE_BUS_ENTITY_DELETED_KEY = 'deleted';

	/**
	 * MODULES
	 */

	/**
	 * Accounts module
	 */

	// Accounts
	public const MESSAGE_BUS_ACCOUNT_ENTITY_REPORTED_ROUTING_KEY = self::MESSAGE_BUS_ENTITY_PREFIX_KEY . '.'
	. self::MESSAGE_BUS_ENTITY_REPORTED_KEY . '.account';

	public const MESSAGE_BUS_ACCOUNT_ENTITY_CREATED_ROUTING_KEY = self::MESSAGE_BUS_ENTITY_PREFIX_KEY . '.'
	. self::MESSAGE_BUS_ENTITY_CREATED_KEY . '.account';

	public const MESSAGE_BUS_ACCOUNT_ENTITY_UPDATED_ROUTING_KEY = self::MESSAGE_BUS_ENTITY_PREFIX_KEY . '.'
	. self::MESSAGE_BUS_ENTITY_UPDATED_KEY . '.account';

	public const MESSAGE_BUS_ACCOUNT_ENTITY_DELETED_ROUTING_KEY = self::MESSAGE_BUS_ENTITY_PREFIX_KEY . '.'
	. self::MESSAGE_BUS_ENTITY_DELETED_KEY . '.account';

	// Emails
	public const MESSAGE_BUS_EMAIL_ENTITY_REPORTED_ROUTING_KEY = self::MESSAGE_BUS_ENTITY_PREFIX_KEY . '.'
	. self::MESSAGE_BUS_ENTITY_REPORTED_KEY . '.email';

	public const MESSAGE_BUS_EMAIL_ENTITY_CREATED_ROUTING_KEY = self::MESSAGE_BUS_ENTITY_PREFIX_KEY . '.'
	. self::MESSAGE_BUS_ENTITY_CREATED_KEY . '.email';

	public const MESSAGE_BUS_EMAIL_ENTITY_UPDATED_ROUTING_KEY = self::MESSAGE_BUS_ENTITY_PREFIX_KEY . '.'
	. self::MESSAGE_BUS_ENTITY_UPDATED_KEY . '.email';

	public const MESSAGE_BUS_EMAIL_ENTITY_DELETED_ROUTING_KEY = self::MESSAGE_BUS_ENTITY_PREFIX_KEY . '.'
	. self::MESSAGE_BUS_ENTITY_DELETED_KEY . '.email';

	// Identities
	public const MESSAGE_BUS_IDENTITY_ENTITY_REPORTED_ROUTING_KEY = self::MESSAGE_BUS_ENTITY_PREFIX_KEY . '.'
	. self::MESSAGE_BUS_ENTITY_REPORTED_KEY . '.identity';

	public const MESSAGE_BUS_IDENTITY_ENTITY_CREATED_ROUTING_KEY = self::MESSAGE_BUS_ENTITY_PREFIX_KEY . '.'
	. self::MESSAGE_BUS_ENTITY_CREATED_KEY . '.identity';

	public const MESSAGE_BUS_IDENTITY_ENTITY_UPDATED_ROUTING_KEY = self::MESSAGE_BUS_ENTITY_PREFIX_KEY . '.'
	. self::MESSAGE_BUS_ENTITY_UPDATED_KEY . '.identity';

	public const MESSAGE_BUS_IDENTITY_ENTITY_DELETED_ROUTING_KEY = self::MESSAGE_BUS_ENTITY_PREFIX_KEY . '.'
	. self::MESSAGE_BUS_ENTITY_DELETED_KEY . '.identity';

	// Roles
	public const MESSAGE_BUS_ROLE_ENTITY_REPORTED_ROUTING_KEY = self::MESSAGE_BUS_ENTITY_PREFIX_KEY . '.'
	. self::MESSAGE_BUS_ENTITY_REPORTED_KEY . '.role';

	public const MESSAGE_BUS_ROLE_ENTITY_CREATED_ROUTING_KEY = self::MESSAGE_BUS_ENTITY_PREFIX_KEY . '.'
	. self::MESSAGE_BUS_ENTITY_CREATED_KEY . '.role';

	public const MESSAGE_BUS_ROLE_ENTITY_UPDATED_ROUTING_KEY = self::MESSAGE_BUS_ENTITY_PREFIX_KEY . '.'
	. self::MESSAGE_BUS_ENTITY_UPDATED_KEY . '.role';

	public const MESSAGE_BUS_ROLE_ENTITY_DELETED_ROUTING_KEY = self::MESSAGE_BUS_ENTITY_PREFIX_KEY . '.'
	. self::MESSAGE_BUS_ENTITY_DELETED_KEY . '.role';

	/**
	 * Devices module
	 */

	// Devices
	public const MESSAGE_BUS_DEVICE_ENTITY_REPORTED_ROUTING_KEY = self::MESSAGE_BUS_ENTITY_PREFIX_KEY . '.'
	. self::MESSAGE_BUS_ENTITY_REPORTED_KEY . '.device';

	public const MESSAGE_BUS_DEVICE_ENTITY_CREATED_ROUTING_KEY = self::MESSAGE_BUS_ENTITY_PREFIX_KEY . '.'
	. self::MESSAGE_BUS_ENTITY_CREATED_KEY . '.device';

	public const MESSAGE_BUS_DEVICE_ENTITY_UPDATED_ROUTING_KEY = self::MESSAGE_BUS_ENTITY_PREFIX_KEY . '.'
	. self::MESSAGE_BUS_ENTITY_UPDATED_KEY . '.device';

	public const MESSAGE_BUS_DEVICE_ENTITY_DELETED_ROUTING_KEY = self::MESSAGE_BUS_ENTITY_PREFIX_KEY . '.'
	. self::MESSAGE_BUS_ENTITY_DELETED_KEY . '.device';

	// Devices properties
	public const MESSAGE_BUS_DEVICE_PROPERTY_ENTITY_REPORTED_ROUTING_KEY = self::MESSAGE_BUS_ENTITY_PREFIX_KEY . '.'
	. self::MESSAGE_BUS_ENTITY_REPORTED_KEY . '.device.property';

	public const MESSAGE_BUS_DEVICE_PROPERTY_ENTITY_CREATED_ROUTING_KEY = self::MESSAGE_BUS_ENTITY_PREFIX_KEY . '.'
	. self::MESSAGE_BUS_ENTITY_CREATED_KEY . '.device.property';

	public const MESSAGE_BUS_DEVICE_PROPERTY_ENTITY_UPDATED_ROUTING_KEY = self::MESSAGE_BUS_ENTITY_PREFIX_KEY . '.'
	. self::MESSAGE_BUS_ENTITY_UPDATED_KEY . '.device.property';

	public const MESSAGE_BUS_DEVICE_PROPERTY_ENTITY_DELETED_ROUTING_KEY = self::MESSAGE_BUS_ENTITY_PREFIX_KEY . '.'
	. self::MESSAGE_BUS_ENTITY_DELETED_KEY . '.device.property';

	// Devices control
	public const MESSAGE_BUS_DEVICE_CONTROL_ENTITY_REPORTED_ROUTING_KEY = self::MESSAGE_BUS_ENTITY_PREFIX_KEY . '.'
	. self::MESSAGE_BUS_ENTITY_REPORTED_KEY . '.device.control';

	public const MESSAGE_BUS_DEVICE_CONTROL_ENTITY_CREATED_ROUTING_KEY = self::MESSAGE_BUS_ENTITY_PREFIX_KEY . '.'
	. self::MESSAGE_BUS_ENTITY_CREATED_KEY . '.device.control';

	public const MESSAGE_BUS_DEVICE_CONTROL_ENTITY_UPDATED_ROUTING_KEY = self::MESSAGE_BUS_ENTITY_PREFIX_KEY . '.'
	. self::MESSAGE_BUS_ENTITY_UPDATED_KEY . '.device.control';

	public const MESSAGE_BUS_DEVICE_CONTROL_ENTITY_DELETED_ROUTING_KEY = self::MESSAGE_BUS_ENTITY_PREFIX_KEY . '.'
	. self::MESSAGE_BUS_ENTITY_DELETED_KEY . '.device.control';

	// Channels
	public const MESSAGE_BUS_CHANNEL_ENTITY_REPORTED_ROUTING_KEY = self::MESSAGE_BUS_ENTITY_PREFIX_KEY . '.'
	. self::MESSAGE_BUS_ENTITY_REPORTED_KEY . '.channel';

	public const MESSAGE_BUS_CHANNEL_ENTITY_CREATED_ROUTING_KEY = self::MESSAGE_BUS_ENTITY_PREFIX_KEY . '.'
	. self::MESSAGE_BUS_ENTITY_CREATED_KEY . '.channel';

	public const MESSAGE_BUS_CHANNEL_ENTITY_UPDATED_ROUTING_KEY = self::MESSAGE_BUS_ENTITY_PREFIX_KEY . '.'
	. self::MESSAGE_BUS_ENTITY_UPDATED_KEY . '.channel';

	public const MESSAGE_BUS_CHANNEL_ENTITY_DELETED_ROUTING_KEY = self::MESSAGE_BUS_ENTITY_PREFIX_KEY . '.'
	. self::MESSAGE_BUS_ENTITY_DELETED_KEY . '.channel';

	// Channels properties
	public const MESSAGE_BUS_CHANNEL_PROPERTY_ENTITY_REPORTED_ROUTING_KEY = self::MESSAGE_BUS_ENTITY_PREFIX_KEY . '.'
	. self::MESSAGE_BUS_ENTITY_REPORTED_KEY . '.channel.property';

	public const MESSAGE_BUS_CHANNEL_PROPERTY_ENTITY_CREATED_ROUTING_KEY = self::MESSAGE_BUS_ENTITY_PREFIX_KEY . '.'
	. self::MESSAGE_BUS_ENTITY_CREATED_KEY . '.channel.property';

	public const MESSAGE_BUS_CHANNEL_PROPERTY_ENTITY_UPDATED_ROUTING_KEY = self::MESSAGE_BUS_ENTITY_PREFIX_KEY . '.'
	. self::MESSAGE_BUS_ENTITY_UPDATED_KEY . '.channel.property';

	public const MESSAGE_BUS_CHANNEL_PROPERTY_ENTITY_DELETED_ROUTING_KEY = self::MESSAGE_BUS_ENTITY_PREFIX_KEY . '.'
	. self::MESSAGE_BUS_ENTITY_DELETED_KEY . '.channel.property';

	// Channels control
	public const MESSAGE_BUS_CHANNEL_CONTROL_ENTITY_REPORTED_ROUTING_KEY = self::MESSAGE_BUS_ENTITY_PREFIX_KEY . '.'
	. self::MESSAGE_BUS_ENTITY_REPORTED_KEY . '.channel.control';

	public const MESSAGE_BUS_CHANNEL_CONTROL_ENTITY_CREATED_ROUTING_KEY = self::MESSAGE_BUS_ENTITY_PREFIX_KEY . '.'
	. self::MESSAGE_BUS_ENTITY_CREATED_KEY . '.channel.control';

	public const MESSAGE_BUS_CHANNEL_CONTROL_ENTITY_UPDATED_ROUTING_KEY = self::MESSAGE_BUS_ENTITY_PREFIX_KEY . '.'
	. self::MESSAGE_BUS_ENTITY_UPDATED_KEY . '.channel.control';

	public const MESSAGE_BUS_CHANNEL_CONTROL_ENTITY_DELETED_ROUTING_KEY = self::MESSAGE_BUS_ENTITY_PREFIX_KEY . '.'
	. self::MESSAGE_BUS_ENTITY_DELETED_KEY . '.channel.control';

	// Connectors
	public const MESSAGE_BUS_CONNECTOR_ENTITY_REPORTED_ROUTING_KEY = self::MESSAGE_BUS_ENTITY_PREFIX_KEY . '.'
	. self::MESSAGE_BUS_ENTITY_REPORTED_KEY . '.connector';

	public const MESSAGE_BUS_CONNECTOR_ENTITY_CREATED_ROUTING_KEY = self::MESSAGE_BUS_ENTITY_PREFIX_KEY . '.'
	. self::MESSAGE_BUS_ENTITY_CREATED_KEY . '.connector';

	public const MESSAGE_BUS_CONNECTOR_ENTITY_UPDATED_ROUTING_KEY = self::MESSAGE_BUS_ENTITY_PREFIX_KEY . '.'
	. self::MESSAGE_BUS_ENTITY_UPDATED_KEY . '.connector';

	public const MESSAGE_BUS_CONNECTOR_ENTITY_DELETED_ROUTING_KEY = self::MESSAGE_BUS_ENTITY_PREFIX_KEY . '.'
	. self::MESSAGE_BUS_ENTITY_DELETED_KEY . '.connector';

	// Connectors properties
	public const MESSAGE_BUS_CONNECTOR_PROPERTY_ENTITY_REPORTED_ROUTING_KEY = self::MESSAGE_BUS_ENTITY_PREFIX_KEY . '.'
	. self::MESSAGE_BUS_ENTITY_REPORTED_KEY . '.connector.property';

	public const MESSAGE_BUS_CONNECTOR_PROPERTY_ENTITY_CREATED_ROUTING_KEY = self::MESSAGE_BUS_ENTITY_PREFIX_KEY . '.'
	. self::MESSAGE_BUS_ENTITY_CREATED_KEY . '.connector.property';

	public const MESSAGE_BUS_CONNECTOR_PROPERTY_ENTITY_UPDATED_ROUTING_KEY = self::MESSAGE_BUS_ENTITY_PREFIX_KEY . '.'
	. self::MESSAGE_BUS_ENTITY_UPDATED_KEY . '.connector.property';

	public const MESSAGE_BUS_CONNECTOR_PROPERTY_ENTITY_DELETED_ROUTING_KEY = self::MESSAGE_BUS_ENTITY_PREFIX_KEY . '.'
	. self::MESSAGE_BUS_ENTITY_DELETED_KEY . '.connector.property';

	// Connectors control
	public const MESSAGE_BUS_CONNECTOR_CONTROL_ENTITY_REPORTED_ROUTING_KEY = self::MESSAGE_BUS_ENTITY_PREFIX_KEY . '.'
	. self::MESSAGE_BUS_ENTITY_REPORTED_KEY . '.connector.control';

	public const MESSAGE_BUS_CONNECTOR_CONTROL_ENTITY_CREATED_ROUTING_KEY = self::MESSAGE_BUS_ENTITY_PREFIX_KEY . '.'
	. self::MESSAGE_BUS_ENTITY_CREATED_KEY . '.connector.control';

	public const MESSAGE_BUS_CONNECTOR_CONTROL_ENTITY_UPDATED_ROUTING_KEY = self::MESSAGE_BUS_ENTITY_PREFIX_KEY . '.'
	. self::MESSAGE_BUS_ENTITY_UPDATED_KEY . '.connector.control';

	public const MESSAGE_BUS_CONNECTOR_CONTROL_ENTITY_DELETED_ROUTING_KEY = self::MESSAGE_BUS_ENTITY_PREFIX_KEY . '.'
	. self::MESSAGE_BUS_ENTITY_DELETED_KEY . '.connector.control';

	/**
	 * Triggers module
	 */

	// Triggers
	public const MESSAGE_BUS_TRIGGER_ENTITY_REPORTED_ROUTING_KEY = self::MESSAGE_BUS_ENTITY_PREFIX_KEY . '.'
	. self::MESSAGE_BUS_ENTITY_REPORTED_KEY . '.trigger';

	public const MESSAGE_BUS_TRIGGER_ENTITY_CREATED_ROUTING_KEY = self::MESSAGE_BUS_ENTITY_PREFIX_KEY . '.'
	. self::MESSAGE_BUS_ENTITY_CREATED_KEY . '.trigger';

	public const MESSAGE_BUS_TRIGGER_ENTITY_UPDATED_ROUTING_KEY = self::MESSAGE_BUS_ENTITY_PREFIX_KEY . '.'
	. self::MESSAGE_BUS_ENTITY_UPDATED_KEY . '.trigger';

	public const MESSAGE_BUS_TRIGGER_ENTITY_DELETED_ROUTING_KEY = self::MESSAGE_BUS_ENTITY_PREFIX_KEY . '.'
	. self::MESSAGE_BUS_ENTITY_DELETED_KEY . '.trigger';

	// Triggers control
	public const MESSAGE_BUS_TRIGGER_CONTROL_ENTITY_REPORTED_ROUTING_KEY = self::MESSAGE_BUS_ENTITY_PREFIX_KEY . '.'
	. self::MESSAGE_BUS_ENTITY_REPORTED_KEY . '.trigger.control';

	public const MESSAGE_BUS_TRIGGER_CONTROL_ENTITY_CREATED_ROUTING_KEY = self::MESSAGE_BUS_ENTITY_PREFIX_KEY . '.'
	. self::MESSAGE_BUS_ENTITY_CREATED_KEY . '.trigger.control';

	public const MESSAGE_BUS_TRIGGER_CONTROL_ENTITY_UPDATED_ROUTING_KEY = self::MESSAGE_BUS_ENTITY_PREFIX_KEY . '.'
	. self::MESSAGE_BUS_ENTITY_UPDATED_KEY . '.trigger.control';

	public const MESSAGE_BUS_TRIGGER_CONTROL_ENTITY_DELETED_ROUTING_KEY = self::MESSAGE_BUS_ENTITY_PREFIX_KEY . '.'
	. self::MESSAGE_BUS_ENTITY_DELETED_KEY . '.trigger.control';

	// Triggers actions
	public const MESSAGE_BUS_TRIGGER_ACTION_ENTITY_REPORTED_ROUTING_KEY = self::MESSAGE_BUS_ENTITY_PREFIX_KEY . '.'
	. self::MESSAGE_BUS_ENTITY_REPORTED_KEY . '.trigger.action';

	public const MESSAGE_BUS_TRIGGER_ACTION_ENTITY_CREATED_ROUTING_KEY = self::MESSAGE_BUS_ENTITY_PREFIX_KEY . '.'
	. self::MESSAGE_BUS_ENTITY_CREATED_KEY . '.trigger.action';

	public const MESSAGE_BUS_TRIGGER_ACTION_ENTITY_UPDATED_ROUTING_KEY = self::MESSAGE_BUS_ENTITY_PREFIX_KEY . '.'
	. self::MESSAGE_BUS_ENTITY_UPDATED_KEY . '.trigger.action';

	public const MESSAGE_BUS_TRIGGER_ACTION_ENTITY_DELETED_ROUTING_KEY = self::MESSAGE_BUS_ENTITY_PREFIX_KEY . '.'
	. self::MESSAGE_BUS_ENTITY_DELETED_KEY . '.trigger.action';

	// Triggers notifications
	public const MESSAGE_BUS_TRIGGER_NOTIFICATION_ENTITY_REPORTED_ROUTING_KEY = self::MESSAGE_BUS_ENTITY_PREFIX_KEY . '.'
	. self::MESSAGE_BUS_ENTITY_REPORTED_KEY . '.trigger.notification';

	public const MESSAGE_BUS_TRIGGER_NOTIFICATION_ENTITY_CREATED_ROUTING_KEY = self::MESSAGE_BUS_ENTITY_PREFIX_KEY . '.'
	. self::MESSAGE_BUS_ENTITY_CREATED_KEY . '.trigger.notification';

	public const MESSAGE_BUS_TRIGGER_NOTIFICATION_ENTITY_UPDATED_ROUTING_KEY = self::MESSAGE_BUS_ENTITY_PREFIX_KEY . '.'
	. self::MESSAGE_BUS_ENTITY_UPDATED_KEY . '.trigger.notification';

	public const MESSAGE_BUS_TRIGGER_NOTIFICATION_ENTITY_DELETED_ROUTING_KEY = self::MESSAGE_BUS_ENTITY_PREFIX_KEY . '.'
	. self::MESSAGE_BUS_ENTITY_DELETED_KEY . '.trigger.notification';

	// Triggers conditions
	public const MESSAGE_BUS_TRIGGER_CONDITION_ENTITY_REPORTED_ROUTING_KEY = self::MESSAGE_BUS_ENTITY_PREFIX_KEY . '.'
	. self::MESSAGE_BUS_ENTITY_REPORTED_KEY . '.trigger.condition';

	public const MESSAGE_BUS_TRIGGER_CONDITION_ENTITY_CREATED_ROUTING_KEY = self::MESSAGE_BUS_ENTITY_PREFIX_KEY . '.'
	. self::MESSAGE_BUS_ENTITY_CREATED_KEY . '.trigger.condition';

	public const MESSAGE_BUS_TRIGGER_CONDITION_ENTITY_UPDATED_ROUTING_KEY = self::MESSAGE_BUS_ENTITY_PREFIX_KEY . '.'
	. self::MESSAGE_BUS_ENTITY_UPDATED_KEY . '.trigger.condition';

	public const MESSAGE_BUS_TRIGGER_CONDITION_ENTITY_DELETED_ROUTING_KEY = self::MESSAGE_BUS_ENTITY_PREFIX_KEY . '.'
	. self::MESSAGE_BUS_ENTITY_DELETED_KEY . '.trigger.condition';

	/*
	 * JSON schemas mapping
	 */

	public const JSON_SCHEMAS_MAPPING = [
		self::MESSAGE_BUS_CONNECTOR_CONTROL_ACTION_ROUTING_KEY => self::RESOURCES_FOLDER . DS
			. 'schemas' . DS . 'actions' . DS . 'action.connector.control.json',
		self::MESSAGE_BUS_CONNECTOR_PROPERTY_ACTION_ROUTING_KEY => self::RESOURCES_FOLDER . DS
			. 'schemas' . DS . 'actions' . DS . 'action.connector.property.json',
		self::MESSAGE_BUS_DEVICE_CONTROL_ACTION_ROUTING_KEY => self::RESOURCES_FOLDER . DS
			. 'schemas' . DS . 'actions' . DS . 'action.device.control.json',
		self::MESSAGE_BUS_DEVICE_PROPERTY_ACTION_ROUTING_KEY => self::RESOURCES_FOLDER . DS
			. 'schemas' . DS . 'actions' . DS . 'action.device.property.json',
		self::MESSAGE_BUS_CHANNEL_CONTROL_ACTION_ROUTING_KEY => self::RESOURCES_FOLDER . DS
			. 'schemas' . DS . 'actions' . DS . 'action.channel.control.json',
		self::MESSAGE_BUS_CHANNEL_PROPERTY_ACTION_ROUTING_KEY => self::RESOURCES_FOLDER . DS
			. 'schemas' . DS . 'actions' . DS . 'action.channel.property.json',
		self::MESSAGE_BUS_TRIGGER_CONTROL_ACTION_ROUTING_KEY => self::RESOURCES_FOLDER . DS
			. 'schemas' . DS . 'actions' . DS . 'action.trigger.control.json',

		self::MESSAGE_BUS_ACCOUNT_ENTITY_REPORTED_ROUTING_KEY => self::RESOURCES_FOLDER . DS
			. 'schemas' . DS . 'modules' . DS . 'accounts-module' . DS . 'entity.account.json',
		self::MESSAGE_BUS_ACCOUNT_ENTITY_CREATED_ROUTING_KEY => self::RESOURCES_FOLDER . DS
			. 'schemas' . DS . 'modules' . DS . 'accounts-module' . DS . 'entity.account.json',
		self::MESSAGE_BUS_ACCOUNT_ENTITY_UPDATED_ROUTING_KEY => self::RESOURCES_FOLDER . DS
			. 'schemas' . DS . 'modules' . DS . 'accounts-module' . DS . 'entity.account.json',
		self::MESSAGE_BUS_ACCOUNT_ENTITY_DELETED_ROUTING_KEY => self::RESOURCES_FOLDER . DS
			. 'schemas' . DS . 'modules' . DS . 'accounts-module' . DS . 'entity.account.json',

		self::MESSAGE_BUS_EMAIL_ENTITY_REPORTED_ROUTING_KEY => self::RESOURCES_FOLDER . DS
			. 'schemas' . DS . 'modules' . DS . 'accounts-module' . DS . 'entity.email.json',
		self::MESSAGE_BUS_EMAIL_ENTITY_CREATED_ROUTING_KEY => self::RESOURCES_FOLDER . DS
			. 'schemas' . DS . 'modules' . DS . 'accounts-module' . DS . 'entity.email.json',
		self::MESSAGE_BUS_EMAIL_ENTITY_UPDATED_ROUTING_KEY => self::RESOURCES_FOLDER . DS
			. 'schemas' . DS . 'modules' . DS . 'accounts-module' . DS . 'entity.email.json',
		self::MESSAGE_BUS_EMAIL_ENTITY_DELETED_ROUTING_KEY => self::RESOURCES_FOLDER . DS
			. 'schemas' . DS . 'modules' . DS . 'accounts-module' . DS . 'entity.email.json',

		self::MESSAGE_BUS_IDENTITY_ENTITY_REPORTED_ROUTING_KEY => self::RESOURCES_FOLDER . DS
			. 'schemas' . DS . 'modules' . DS . 'accounts-module' . DS . 'entity.identity.json',
		self::MESSAGE_BUS_IDENTITY_ENTITY_CREATED_ROUTING_KEY => self::RESOURCES_FOLDER . DS
			. 'schemas' . DS . 'modules' . DS . 'accounts-module' . DS . 'entity.identity.json',
		self::MESSAGE_BUS_IDENTITY_ENTITY_UPDATED_ROUTING_KEY => self::RESOURCES_FOLDER . DS
			. 'schemas' . DS . 'modules' . DS . 'accounts-module' . DS . 'entity.identity.json',
		self::MESSAGE_BUS_IDENTITY_ENTITY_DELETED_ROUTING_KEY => self::RESOURCES_FOLDER . DS
			. 'schemas' . DS . 'modules' . DS . 'accounts-module' . DS . 'entity.identity.json',

		self::MESSAGE_BUS_ROLE_ENTITY_REPORTED_ROUTING_KEY => self::RESOURCES_FOLDER . DS
			. 'schemas' . DS . 'modules' . DS . 'accounts-module' . DS . 'entity.role.json',
		self::MESSAGE_BUS_ROLE_ENTITY_CREATED_ROUTING_KEY => self::RESOURCES_FOLDER . DS
			. 'schemas' . DS . 'modules' . DS . 'accounts-module' . DS . 'entity.role.json',
		self::MESSAGE_BUS_ROLE_ENTITY_UPDATED_ROUTING_KEY => self::RESOURCES_FOLDER . DS
			. 'schemas' . DS . 'modules' . DS . 'accounts-module' . DS . 'entity.role.json',
		self::MESSAGE_BUS_ROLE_ENTITY_DELETED_ROUTING_KEY => self::RESOURCES_FOLDER . DS
			. 'schemas' . DS . 'modules' . DS . 'accounts-module' . DS . 'entity.role.json',

		self::MESSAGE_BUS_DEVICE_ENTITY_REPORTED_ROUTING_KEY => self::RESOURCES_FOLDER . DS
			. 'schemas' . DS . 'modules' . DS . 'devices-module' . DS . 'entity.device.json',
		self::MESSAGE_BUS_DEVICE_ENTITY_CREATED_ROUTING_KEY => self::RESOURCES_FOLDER . DS
			. 'schemas' . DS . 'modules' . DS . 'devices-module' . DS . 'entity.device.json',
		self::MESSAGE_BUS_DEVICE_ENTITY_UPDATED_ROUTING_KEY => self::RESOURCES_FOLDER . DS
			. 'schemas' . DS . 'modules' . DS . 'devices-module' . DS . 'entity.device.json',
		self::MESSAGE_BUS_DEVICE_ENTITY_DELETED_ROUTING_KEY => self::RESOURCES_FOLDER . DS
			. 'schemas' . DS . 'modules' . DS . 'devices-module' . DS . 'entity.device.json',

		self::MESSAGE_BUS_DEVICE_PROPERTY_ENTITY_REPORTED_ROUTING_KEY => self::RESOURCES_FOLDER . DS
			. 'schemas' . DS . 'modules' . DS . 'devices-module' . DS . 'entity.device.property.json',
		self::MESSAGE_BUS_DEVICE_PROPERTY_ENTITY_CREATED_ROUTING_KEY => self::RESOURCES_FOLDER . DS
			. 'schemas' . DS . 'modules' . DS . 'devices-module' . DS . 'entity.device.property.json',
		self::MESSAGE_BUS_DEVICE_PROPERTY_ENTITY_UPDATED_ROUTING_KEY => self::RESOURCES_FOLDER . DS
			. 'schemas' . DS . 'modules' . DS . 'devices-module' . DS . 'entity.device.property.json',
		self::MESSAGE_BUS_DEVICE_PROPERTY_ENTITY_DELETED_ROUTING_KEY => self::RESOURCES_FOLDER . DS
			. 'schemas' . DS . 'modules' . DS . 'devices-module' . DS . 'entity.device.property.json',

		self::MESSAGE_BUS_DEVICE_CONTROL_ENTITY_REPORTED_ROUTING_KEY => self::RESOURCES_FOLDER . DS
			. 'schemas' . DS . 'modules' . DS . 'devices-module' . DS . 'entity.device.control.json',
		self::MESSAGE_BUS_DEVICE_CONTROL_ENTITY_CREATED_ROUTING_KEY => self::RESOURCES_FOLDER . DS
			. 'schemas' . DS . 'modules' . DS . 'devices-module' . DS . 'entity.device.control.json',
		self::MESSAGE_BUS_DEVICE_CONTROL_ENTITY_UPDATED_ROUTING_KEY => self::RESOURCES_FOLDER . DS
			. 'schemas' . DS . 'modules' . DS . 'devices-module' . DS . 'entity.device.control.json',
		self::MESSAGE_BUS_DEVICE_CONTROL_ENTITY_DELETED_ROUTING_KEY => self::RESOURCES_FOLDER . DS
			. 'schemas' . DS . 'modules' . DS . 'devices-module' . DS . 'entity.device.control.json',

		self::MESSAGE_BUS_CHANNEL_ENTITY_REPORTED_ROUTING_KEY => self::RESOURCES_FOLDER . DS
			. 'schemas' . DS . 'modules' . DS . 'devices-module' . DS . 'entity.channel.json',
		self::MESSAGE_BUS_CHANNEL_ENTITY_CREATED_ROUTING_KEY => self::RESOURCES_FOLDER . DS
			. 'schemas' . DS . 'modules' . DS . 'devices-module' . DS . 'entity.channel.json',
		self::MESSAGE_BUS_CHANNEL_ENTITY_UPDATED_ROUTING_KEY => self::RESOURCES_FOLDER . DS
			. 'schemas' . DS . 'modules' . DS . 'devices-module' . DS . 'entity.channel.json',
		self::MESSAGE_BUS_CHANNEL_ENTITY_DELETED_ROUTING_KEY => self::RESOURCES_FOLDER . DS
			. 'schemas' . DS . 'modules' . DS . 'devices-module' . DS . 'entity.channel.json',

		self::MESSAGE_BUS_CHANNEL_PROPERTY_ENTITY_REPORTED_ROUTING_KEY => self::RESOURCES_FOLDER . DS
			. 'schemas' . DS . 'modules' . DS . 'devices-module' . DS . 'entity.channel.property.json',
		self::MESSAGE_BUS_CHANNEL_PROPERTY_ENTITY_CREATED_ROUTING_KEY => self::RESOURCES_FOLDER . DS
			. 'schemas' . DS . 'modules' . DS . 'devices-module' . DS . 'entity.channel.property.json',
		self::MESSAGE_BUS_CHANNEL_PROPERTY_ENTITY_UPDATED_ROUTING_KEY => self::RESOURCES_FOLDER . DS
			. 'schemas' . DS . 'modules' . DS . 'devices-module' . DS . 'entity.channel.property.json',
		self::MESSAGE_BUS_CHANNEL_PROPERTY_ENTITY_DELETED_ROUTING_KEY => self::RESOURCES_FOLDER . DS
			. 'schemas' . DS . 'modules' . DS . 'devices-module' . DS . 'entity.channel.property.json',

		self::MESSAGE_BUS_CHANNEL_CONTROL_ENTITY_REPORTED_ROUTING_KEY => self::RESOURCES_FOLDER . DS
			. 'schemas' . DS . 'modules' . DS . 'devices-module' . DS . 'entity.channel.control.json',
		self::MESSAGE_BUS_CHANNEL_CONTROL_ENTITY_CREATED_ROUTING_KEY => self::RESOURCES_FOLDER . DS
			. 'schemas' . DS . 'modules' . DS . 'devices-module' . DS . 'entity.channel.control.json',
		self::MESSAGE_BUS_CHANNEL_CONTROL_ENTITY_UPDATED_ROUTING_KEY => self::RESOURCES_FOLDER . DS
			. 'schemas' . DS . 'modules' . DS . 'devices-module' . DS . 'entity.channel.control.json',
		self::MESSAGE_BUS_CHANNEL_CONTROL_ENTITY_DELETED_ROUTING_KEY => self::RESOURCES_FOLDER . DS
			. 'schemas' . DS . 'modules' . DS . 'devices-module' . DS . 'entity.channel.control.json',

		self::MESSAGE_BUS_CONNECTOR_ENTITY_REPORTED_ROUTING_KEY => self::RESOURCES_FOLDER . DS
			. 'schemas' . DS . 'modules' . DS . 'devices-module' . DS . 'entity.connector.json',
		self::MESSAGE_BUS_CONNECTOR_ENTITY_CREATED_ROUTING_KEY => self::RESOURCES_FOLDER . DS
			. 'schemas' . DS . 'modules' . DS . 'devices-module' . DS . 'entity.connector.json',
		self::MESSAGE_BUS_CONNECTOR_ENTITY_UPDATED_ROUTING_KEY => self::RESOURCES_FOLDER . DS
			. 'schemas' . DS . 'modules' . DS . 'devices-module' . DS . 'entity.connector.json',
		self::MESSAGE_BUS_CONNECTOR_ENTITY_DELETED_ROUTING_KEY => self::RESOURCES_FOLDER . DS
			. 'schemas' . DS . 'modules' . DS . 'devices-module' . DS . 'entity.connector.json',

		self::MESSAGE_BUS_CONNECTOR_PROPERTY_ENTITY_REPORTED_ROUTING_KEY => self::RESOURCES_FOLDER . DS
			. 'schemas' . DS . 'modules' . DS . 'devices-module' . DS . 'entity.connector.property.json',
		self::MESSAGE_BUS_CONNECTOR_PROPERTY_ENTITY_CREATED_ROUTING_KEY => self::RESOURCES_FOLDER . DS
			. 'schemas' . DS . 'modules' . DS . 'devices-module' . DS . 'entity.connector.property.json',
		self::MESSAGE_BUS_CONNECTOR_PROPERTY_ENTITY_UPDATED_ROUTING_KEY => self::RESOURCES_FOLDER . DS
			. 'schemas' . DS . 'modules' . DS . 'devices-module' . DS . 'entity.connector.property.json',
		self::MESSAGE_BUS_CONNECTOR_PROPERTY_ENTITY_DELETED_ROUTING_KEY => self::RESOURCES_FOLDER . DS
			. 'schemas' . DS . 'modules' . DS . 'devices-module' . DS . 'entity.connector.property.json',

		self::MESSAGE_BUS_CONNECTOR_CONTROL_ENTITY_REPORTED_ROUTING_KEY => self::RESOURCES_FOLDER . DS
			. 'schemas' . DS . 'modules' . DS . 'devices-module' . DS . 'entity.connector.control.json',
		self::MESSAGE_BUS_CONNECTOR_CONTROL_ENTITY_CREATED_ROUTING_KEY => self::RESOURCES_FOLDER . DS
			. 'schemas' . DS . 'modules' . DS . 'devices-module' . DS . 'entity.connector.control.json',
		self::MESSAGE_BUS_CONNECTOR_CONTROL_ENTITY_UPDATED_ROUTING_KEY => self::RESOURCES_FOLDER . DS
			. 'schemas' . DS . 'modules' . DS . 'devices-module' . DS . 'entity.connector.control.json',
		self::MESSAGE_BUS_CONNECTOR_CONTROL_ENTITY_DELETED_ROUTING_KEY => self::RESOURCES_FOLDER . DS
			. 'schemas' . DS . 'modules' . DS . 'devices-module' . DS . 'entity.connector.control.json',

		self::MESSAGE_BUS_TRIGGER_ENTITY_REPORTED_ROUTING_KEY => self::RESOURCES_FOLDER . DS
			. 'schemas' . DS . 'modules' . DS . 'triggers-module' . DS . 'entity.trigger.json',
		self::MESSAGE_BUS_TRIGGER_ENTITY_CREATED_ROUTING_KEY => self::RESOURCES_FOLDER . DS
			. 'schemas' . DS . 'modules' . DS . 'triggers-module' . DS . 'entity.trigger.json',
		self::MESSAGE_BUS_TRIGGER_ENTITY_UPDATED_ROUTING_KEY => self::RESOURCES_FOLDER . DS
			. 'schemas' . DS . 'modules' . DS . 'triggers-module' . DS . 'entity.trigger.json',
		self::MESSAGE_BUS_TRIGGER_ENTITY_DELETED_ROUTING_KEY => self::RESOURCES_FOLDER . DS
			. 'schemas' . DS . 'modules' . DS . 'triggers-module' . DS . 'entity.trigger.json',

		self::MESSAGE_BUS_TRIGGER_CONTROL_ENTITY_REPORTED_ROUTING_KEY => self::RESOURCES_FOLDER . DS
			. 'schemas' . DS . 'modules' . DS . 'triggers-module' . DS . 'entity.trigger.control.json',
		self::MESSAGE_BUS_TRIGGER_CONTROL_ENTITY_CREATED_ROUTING_KEY => self::RESOURCES_FOLDER . DS
			. 'schemas' . DS . 'modules' . DS . 'triggers-module' . DS . 'entity.trigger.control.json',
		self::MESSAGE_BUS_TRIGGER_CONTROL_ENTITY_UPDATED_ROUTING_KEY => self::RESOURCES_FOLDER . DS
			. 'schemas' . DS . 'modules' . DS . 'triggers-module' . DS . 'entity.trigger.control.json',
		self::MESSAGE_BUS_TRIGGER_CONTROL_ENTITY_DELETED_ROUTING_KEY => self::RESOURCES_FOLDER . DS
			. 'schemas' . DS . 'modules' . DS . 'triggers-module' . DS . 'entity.trigger.control.json',

		self::MESSAGE_BUS_TRIGGER_ACTION_ENTITY_REPORTED_ROUTING_KEY => self::RESOURCES_FOLDER . DS
			. 'schemas' . DS . 'modules' . DS . 'triggers-module' . DS . 'entity.action.json',
		self::MESSAGE_BUS_TRIGGER_ACTION_ENTITY_CREATED_ROUTING_KEY => self::RESOURCES_FOLDER . DS
			. 'schemas' . DS . 'modules' . DS . 'triggers-module' . DS . 'entity.action.json',
		self::MESSAGE_BUS_TRIGGER_ACTION_ENTITY_UPDATED_ROUTING_KEY => self::RESOURCES_FOLDER . DS
			. 'schemas' . DS . 'modules' . DS . 'triggers-module' . DS . 'entity.action.json',
		self::MESSAGE_BUS_TRIGGER_ACTION_ENTITY_DELETED_ROUTING_KEY => self::RESOURCES_FOLDER . DS
			. 'schemas' . DS . 'modules' . DS . 'triggers-module' . DS . 'entity.action.json',

		self::MESSAGE_BUS_TRIGGER_NOTIFICATION_ENTITY_REPORTED_ROUTING_KEY => self::RESOURCES_FOLDER . DS
			. 'schemas' . DS . 'modules' . DS . 'triggers-module' . DS . 'entity.notification.json',
		self::MESSAGE_BUS_TRIGGER_NOTIFICATION_ENTITY_CREATED_ROUTING_KEY => self::RESOURCES_FOLDER . DS
			. 'schemas' . DS . 'modules' . DS . 'triggers-module' . DS . 'entity.notification.json',
		self::MESSAGE_BUS_TRIGGER_NOTIFICATION_ENTITY_UPDATED_ROUTING_KEY => self::RESOURCES_FOLDER . DS
			. 'schemas' . DS . 'modules' . DS . 'triggers-module' . DS . 'entity.notification.json',
		self::MESSAGE_BUS_TRIGGER_NOTIFICATION_ENTITY_DELETED_ROUTING_KEY => self::RESOURCES_FOLDER . DS
			. 'schemas' . DS . 'modules' . DS . 'triggers-module' . DS . 'entity.notification.json',

		self::MESSAGE_BUS_TRIGGER_CONDITION_ENTITY_REPORTED_ROUTING_KEY => self::RESOURCES_FOLDER . DS
			. 'schemas' . DS . 'modules' . DS . 'triggers-module' . DS . 'entity.condition.json',
		self::MESSAGE_BUS_TRIGGER_CONDITION_ENTITY_CREATED_ROUTING_KEY => self::RESOURCES_FOLDER . DS
			. 'schemas' . DS . 'modules' . DS . 'triggers-module' . DS . 'entity.condition.json',
		self::MESSAGE_BUS_TRIGGER_CONDITION_ENTITY_UPDATED_ROUTING_KEY => self::RESOURCES_FOLDER . DS
			. 'schemas' . DS . 'modules' . DS . 'triggers-module' . DS . 'entity.condition.json',
		self::MESSAGE_BUS_TRIGGER_CONDITION_ENTITY_DELETED_ROUTING_KEY => self::RESOURCES_FOLDER . DS
			. 'schemas' . DS . 'modules' . DS . 'triggers-module' . DS . 'entity.condition.json',
	];

	/**
	 * Value format
	 */

	// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
	public const VALUE_FORMAT_NUMBER_RANGE = '/^(?:(?:(?:i8|u8|i16|u16|i32|u32|f){1}\|)?(?:(?:\-)?(?:\d)*(?:.(?:\d)+)?))?(?:\:(?:(?:(?:i8|u8|i16|u16|i32|u32|f){1}\|)?(?:\d)*(?:.(?:\d)+)?)){1}$/';

	public const VALUE_FORMAT_STRING_ENUM = '/^(?:[a-zA-Z](?:[a-zA-Z0-9]-?_?)*)(?:\,(?:[a-zA-Z](?:[a-zA-Z0-9]-?_?)*))*(?:,)?$/';
	// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
	public const VALUE_FORMAT_COMBINED_ENUM = '/^(?:(?:(?:i8|u8|i16|u16|i32|u32|f|b|s|btn|sw){1}\|)?(?:[a-zA-Z0-9]-?_?)*)(?:\:(?:(?:i8|u8|i16|u16|i32|u32|f|b|s|btn|sw){1}\|)?(?:[a-zA-Z0-9]-?_?)*){2}(?:,(?:(?:(?:i8|u8|i16|u16|i32|u32|f|b|s|btn|sw){1}\|)?(?:[a-zA-Z0-9]-?_?)*)(?:\:(?:(?:i8|u8|i16|u16|i32|u32|f|b|s|btn|sw){1}\|)?(?:[a-zA-Z0-9]-?_?)*){2})*$/';

}
