export enum ActionRoutes {
	CONNECTOR_CONTROL = 'fb.exchange.action.connector.control',
	CONNECTOR_PROPERTY = 'fb.exchange.action.connector.property',
	DEVICE_CONTROL = 'fb.exchange.action.device.control',
	DEVICE_PROPERTY = 'fb.exchange.action.device.property',
	CHANNEL_CONTROL = 'fb.exchange.action.channel.control',
	CHANNEL_PROPERTY = 'fb.exchange.action.channel.property',
	TRIGGER_CONTROL = 'fb.exchange.action.trigger.control',
}

export enum MessageRoutes {
	MODULE = 'fb.exchange.message.module',
	PLUGIN = 'fb.exchange.message.plugin',
	CONNECTOR = 'fb.exchange.message.connector',
	TRIGGER = 'fb.exchange.message.trigger',
}

export enum AccountsModuleRoutes {
	// Accounts
	ACCOUNT_ENTITY_REPORTED = 'fb.exchange.module.entity.reported.account',
	ACCOUNT_ENTITY_CREATED = 'fb.exchange.module.entity.created.account',
	ACCOUNT_ENTITY_UPDATED = 'fb.exchange.module.entity.updated.account',
	ACCOUNT_ENTITY_DELETED = 'fb.exchange.module.entity.deleted.account',

	// Emails
	EMAIL_ENTITY_REPORTED = 'fb.exchange.module.entity.reported.email',
	EMAIL_ENTITY_CREATED = 'fb.exchange.module.entity.created.email',
	EMAIL_ENTITY_UPDATED = 'fb.exchange.module.entity.updated.email',
	EMAIL_ENTITY_DELETED = 'fb.exchange.module.entity.deleted.email',

	// Identities
	IDENTITY_ENTITY_REPORTED = 'fb.exchange.module.entity.reported.identity',
	IDENTITY_ENTITY_CREATED = 'fb.exchange.module.entity.created.identity',
	IDENTITY_ENTITY_UPDATED = 'fb.exchange.module.entity.updated.identity',
	IDENTITY_ENTITY_DELETED = 'fb.exchange.module.entity.deleted.identity',

	// Roles
	ROLE_ENTITY_REPORTED = 'fb.exchange.module.entity.reported.role',
	ROLE_ENTITY_CREATED = 'fb.exchange.module.entity.created.role',
	ROLE_ENTITY_UPDATED = 'fb.exchange.module.entity.updated.role',
	ROLE_ENTITY_DELETED = 'fb.exchange.module.entity.deleted.role',
}

export enum DevicesModuleRoutes {
	// Devices
	DEVICE_ENTITY_REPORTED = 'fb.exchange.module.entity.reported.device',
	DEVICE_ENTITY_CREATED = 'fb.exchange.module.entity.created.device',
	DEVICE_ENTITY_UPDATED = 'fb.exchange.module.entity.updated.device',
	DEVICE_ENTITY_DELETED = 'fb.exchange.module.entity.deleted.device',

	// Device's properties
	DEVICE_PROPERTY_ENTITY_REPORTED = 'fb.exchange.module.entity.reported.device.property',
	DEVICE_PROPERTY_ENTITY_CREATED = 'fb.exchange.module.entity.created.device.property',
	DEVICE_PROPERTY_ENTITY_UPDATED = 'fb.exchange.module.entity.updated.device.property',
	DEVICE_PROPERTY_ENTITY_DELETED = 'fb.exchange.module.entity.deleted.device.property',

	// Device's control
	DEVICE_CONTROL_ENTITY_REPORTED = 'fb.exchange.module.entity.reported.device.control',
	DEVICE_CONTROL_ENTITY_CREATED = 'fb.exchange.module.entity.created.device.control',
	DEVICE_CONTROL_ENTITY_UPDATED = 'fb.exchange.module.entity.updated.device.control',
	DEVICE_CONTROL_ENTITY_DELETED = 'fb.exchange.module.entity.deleted.device.control',

	// Device's attribute
	DEVICE_ATTRIBUTE_ENTITY_REPORTED = 'fb.exchange.module.entity.reported.device.attribute',
	DEVICE_ATTRIBUTE_ENTITY_CREATED = 'fb.exchange.module.entity.created.device.attribute',
	DEVICE_ATTRIBUTE_ENTITY_UPDATED = 'fb.exchange.module.entity.updated.device.attribute',
	DEVICE_ATTRIBUTE_ENTITY_DELETED = 'fb.exchange.module.entity.deleted.device.attribute',

	// Channels
	CHANNEL_ENTITY_REPORTED = 'fb.exchange.module.entity.reported.channel',
	CHANNEL_ENTITY_CREATED = 'fb.exchange.module.entity.created.channel',
	CHANNEL_ENTITY_UPDATED = 'fb.exchange.module.entity.updated.channel',
	CHANNEL_ENTITY_DELETED = 'fb.exchange.module.entity.deleted.channel',

	// Channel's properties
	CHANNEL_PROPERTY_ENTITY_REPORTED = 'fb.exchange.module.entity.reported.channel.property',
	CHANNEL_PROPERTY_ENTITY_CREATED = 'fb.exchange.module.entity.created.channel.property',
	CHANNEL_PROPERTY_ENTITY_UPDATED = 'fb.exchange.module.entity.updated.channel.property',
	CHANNEL_PROPERTY_ENTITY_DELETED = 'fb.exchange.module.entity.deleted.channel.property',

	// Channel's control
	CHANNEL_CONTROL_ENTITY_REPORTED = 'fb.exchange.module.entity.reported.channel.control',
	CHANNEL_CONTROL_ENTITY_CREATED = 'fb.exchange.module.entity.created.channel.control',
	CHANNEL_CONTROL_ENTITY_UPDATED = 'fb.exchange.module.entity.updated.channel.control',
	CHANNEL_CONTROL_ENTITY_DELETED = 'fb.exchange.module.entity.deleted.channel.control',

	// Connectors
	CONNECTOR_ENTITY_REPORTED = 'fb.exchange.module.entity.reported.connector',
	CONNECTOR_ENTITY_CREATED = 'fb.exchange.module.entity.created.connector',
	CONNECTOR_ENTITY_UPDATED = 'fb.exchange.module.entity.updated.connector',
	CONNECTOR_ENTITY_DELETED = 'fb.exchange.module.entity.deleted.connector',

	// Connector's properties
	CONNECTOR_PROPERTY_ENTITY_REPORTED = 'fb.exchange.module.entity.reported.connector.property',
	CONNECTOR_PROPERTY_ENTITY_CREATED = 'fb.exchange.module.entity.created.connector.property',
	CONNECTOR_PROPERTY_ENTITY_UPDATED = 'fb.exchange.module.entity.updated.connector.property',
	CONNECTOR_PROPERTY_ENTITY_DELETED = 'fb.exchange.module.entity.deleted.connector.property',

	// Connector's control
	CONNECTOR_CONTROL_ENTITY_REPORTED = 'fb.exchange.module.entity.reported.connector.control',
	CONNECTOR_CONTROL_ENTITY_CREATED = 'fb.exchange.module.entity.created.connector.control',
	CONNECTOR_CONTROL_ENTITY_UPDATED = 'fb.exchange.module.entity.updated.connector.control',
	CONNECTOR_CONTROL_ENTITY_DELETED = 'fb.exchange.module.entity.deleted.connector.control',
}

export enum TriggersModuleRoutes {
	// Triggers
	TRIGGER_ENTITY_REPORTED = 'fb.exchange.module.entity.reported.trigger',
	TRIGGER_ENTITY_CREATED = 'fb.exchange.module.entity.created.trigger',
	TRIGGER_ENTITY_UPDATED = 'fb.exchange.module.entity.updated.trigger',
	TRIGGER_ENTITY_DELETED = 'fb.exchange.module.entity.deleted.trigger',

	// Trigger's control
	TRIGGER_CONTROL_ENTITY_REPORTED = 'fb.exchange.module.entity.reported.trigger.control',
	TRIGGER_CONTROL_ENTITY_CREATED = 'fb.exchange.module.entity.created.trigger.control',
	TRIGGER_CONTROL_ENTITY_UPDATED = 'fb.exchange.module.entity.updated.trigger.control',
	TRIGGER_CONTROL_ENTITY_DELETED = 'fb.exchange.module.entity.deleted.trigger.control',

	// Actions
	TRIGGER_ACTION_ENTITY_REPORTED = 'fb.exchange.module.entity.reported.trigger.action',
	TRIGGER_ACTION_ENTITY_CREATED = 'fb.exchange.module.entity.created.trigger.action',
	TRIGGER_ACTION_ENTITY_UPDATED = 'fb.exchange.module.entity.updated.trigger.action',
	TRIGGER_ACTION_ENTITY_DELETED = 'fb.exchange.module.entity.deleted.trigger.action',

	// Notifications
	TRIGGER_NOTIFICATION_ENTITY_REPORTED = 'fb.exchange.module.entity.reported.trigger.notification',
	TRIGGER_NOTIFICATION_ENTITY_CREATED = 'fb.exchange.module.entity.created.trigger.notification',
	TRIGGER_NOTIFICATION_ENTITY_UPDATED = 'fb.exchange.module.entity.updated.trigger.notification',
	TRIGGER_NOTIFICATION_ENTITY_DELETED = 'fb.exchange.module.entity.deleted.trigger.notification',

	// Conditions
	TRIGGER_CONDITION_ENTITY_REPORTED = 'fb.exchange.module.entity.reported.trigger.condition',
	TRIGGER_CONDITION_ENTITY_CREATED = 'fb.exchange.module.entity.created.trigger.condition',
	TRIGGER_CONDITION_ENTITY_UPDATED = 'fb.exchange.module.entity.updated.trigger.condition',
	TRIGGER_CONDITION_ENTITY_DELETED = 'fb.exchange.module.entity.deleted.trigger.condition',
}

export enum UiModuleRoutes {}
