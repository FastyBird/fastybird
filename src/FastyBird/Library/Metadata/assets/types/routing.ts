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
	AUTOMATOR = 'fb.exchange.message.automator',
}

export enum AccountsModuleRoutes {
	// Accounts
	ACCOUNT_DOCUMENT_REPORTED = 'fb.exchange.module.document.reported.account',
	ACCOUNT_DOCUMENT_CREATED = 'fb.exchange.module.document.created.account',
	ACCOUNT_DOCUMENT_UPDATED = 'fb.exchange.module.document.updated.account',
	ACCOUNT_DOCUMENT_DELETED = 'fb.exchange.module.document.deleted.account',

	// Emails
	EMAIL_DOCUMENT_REPORTED = 'fb.exchange.module.document.reported.email',
	EMAIL_DOCUMENT_CREATED = 'fb.exchange.module.document.created.email',
	EMAIL_DOCUMENT_UPDATED = 'fb.exchange.module.document.updated.email',
	EMAIL_DOCUMENT_DELETED = 'fb.exchange.module.document.deleted.email',

	// Identities
	IDENTITY_DOCUMENT_REPORTED = 'fb.exchange.module.document.reported.identity',
	IDENTITY_DOCUMENT_CREATED = 'fb.exchange.module.document.created.identity',
	IDENTITY_DOCUMENT_UPDATED = 'fb.exchange.module.document.updated.identity',
	IDENTITY_DOCUMENT_DELETED = 'fb.exchange.module.document.deleted.identity',

	// Roles
	ROLE_DOCUMENT_REPORTED = 'fb.exchange.module.document.reported.role',
	ROLE_DOCUMENT_CREATED = 'fb.exchange.module.document.created.role',
	ROLE_DOCUMENT_UPDATED = 'fb.exchange.module.document.updated.role',
	ROLE_DOCUMENT_DELETED = 'fb.exchange.module.document.deleted.role',
}

export enum DevicesModuleRoutes {
	// Devices
	DEVICE_DOCUMENT_REPORTED = 'fb.exchange.module.document.reported.device',
	DEVICE_DOCUMENT_CREATED = 'fb.exchange.module.document.created.device',
	DEVICE_DOCUMENT_UPDATED = 'fb.exchange.module.document.updated.device',
	DEVICE_DOCUMENT_DELETED = 'fb.exchange.module.document.deleted.device',

	// Device's properties
	DEVICE_PROPERTY_DOCUMENT_REPORTED = 'fb.exchange.module.document.reported.device.property',
	DEVICE_PROPERTY_DOCUMENT_CREATED = 'fb.exchange.module.document.created.device.property',
	DEVICE_PROPERTY_DOCUMENT_UPDATED = 'fb.exchange.module.document.updated.device.property',
	DEVICE_PROPERTY_DOCUMENT_DELETED = 'fb.exchange.module.document.deleted.device.property',

	// Device's control
	DEVICE_CONTROL_DOCUMENT_REPORTED = 'fb.exchange.module.document.reported.device.control',
	DEVICE_CONTROL_DOCUMENT_CREATED = 'fb.exchange.module.document.created.device.control',
	DEVICE_CONTROL_DOCUMENT_UPDATED = 'fb.exchange.module.document.updated.device.control',
	DEVICE_CONTROL_DOCUMENT_DELETED = 'fb.exchange.module.document.deleted.device.control',

	// Channels
	CHANNEL_DOCUMENT_REPORTED = 'fb.exchange.module.document.reported.channel',
	CHANNEL_DOCUMENT_CREATED = 'fb.exchange.module.document.created.channel',
	CHANNEL_DOCUMENT_UPDATED = 'fb.exchange.module.document.updated.channel',
	CHANNEL_DOCUMENT_DELETED = 'fb.exchange.module.document.deleted.channel',

	// Channel's properties
	CHANNEL_PROPERTY_DOCUMENT_REPORTED = 'fb.exchange.module.document.reported.channel.property',
	CHANNEL_PROPERTY_DOCUMENT_CREATED = 'fb.exchange.module.document.created.channel.property',
	CHANNEL_PROPERTY_DOCUMENT_UPDATED = 'fb.exchange.module.document.updated.channel.property',
	CHANNEL_PROPERTY_DOCUMENT_DELETED = 'fb.exchange.module.document.deleted.channel.property',

	// Channel's control
	CHANNEL_CONTROL_DOCUMENT_REPORTED = 'fb.exchange.module.document.reported.channel.control',
	CHANNEL_CONTROL_DOCUMENT_CREATED = 'fb.exchange.module.document.created.channel.control',
	CHANNEL_CONTROL_DOCUMENT_UPDATED = 'fb.exchange.module.document.updated.channel.control',
	CHANNEL_CONTROL_DOCUMENT_DELETED = 'fb.exchange.module.document.deleted.channel.control',

	// Connectors
	CONNECTOR_DOCUMENT_REPORTED = 'fb.exchange.module.document.reported.connector',
	CONNECTOR_DOCUMENT_CREATED = 'fb.exchange.module.document.created.connector',
	CONNECTOR_DOCUMENT_UPDATED = 'fb.exchange.module.document.updated.connector',
	CONNECTOR_DOCUMENT_DELETED = 'fb.exchange.module.document.deleted.connector',

	// Connector's properties
	CONNECTOR_PROPERTY_DOCUMENT_REPORTED = 'fb.exchange.module.document.reported.connector.property',
	CONNECTOR_PROPERTY_DOCUMENT_CREATED = 'fb.exchange.module.document.created.connector.property',
	CONNECTOR_PROPERTY_DOCUMENT_UPDATED = 'fb.exchange.module.document.updated.connector.property',
	CONNECTOR_PROPERTY_DOCUMENT_DELETED = 'fb.exchange.module.document.deleted.connector.property',

	// Connector's control
	CONNECTOR_CONTROL_DOCUMENT_REPORTED = 'fb.exchange.module.document.reported.connector.control',
	CONNECTOR_CONTROL_DOCUMENT_CREATED = 'fb.exchange.module.document.created.connector.control',
	CONNECTOR_CONTROL_DOCUMENT_UPDATED = 'fb.exchange.module.document.updated.connector.control',
	CONNECTOR_CONTROL_DOCUMENT_DELETED = 'fb.exchange.module.document.deleted.connector.control',
}

export enum TriggersModuleRoutes {
	// Triggers
	TRIGGER_DOCUMENT_REPORTED = 'fb.exchange.module.document.reported.trigger',
	TRIGGER_DOCUMENT_CREATED = 'fb.exchange.module.document.created.trigger',
	TRIGGER_DOCUMENT_UPDATED = 'fb.exchange.module.document.updated.trigger',
	TRIGGER_DOCUMENT_DELETED = 'fb.exchange.module.document.deleted.trigger',

	// Trigger's control
	TRIGGER_CONTROL_DOCUMENT_REPORTED = 'fb.exchange.module.document.reported.trigger.control',
	TRIGGER_CONTROL_DOCUMENT_CREATED = 'fb.exchange.module.document.created.trigger.control',
	TRIGGER_CONTROL_DOCUMENT_UPDATED = 'fb.exchange.module.document.updated.trigger.control',
	TRIGGER_CONTROL_DOCUMENT_DELETED = 'fb.exchange.module.document.deleted.trigger.control',

	// Actions
	TRIGGER_ACTION_DOCUMENT_REPORTED = 'fb.exchange.module.document.reported.trigger.action',
	TRIGGER_ACTION_DOCUMENT_CREATED = 'fb.exchange.module.document.created.trigger.action',
	TRIGGER_ACTION_DOCUMENT_UPDATED = 'fb.exchange.module.document.updated.trigger.action',
	TRIGGER_ACTION_DOCUMENT_DELETED = 'fb.exchange.module.document.deleted.trigger.action',

	// Notifications
	TRIGGER_NOTIFICATION_DOCUMENT_REPORTED = 'fb.exchange.module.document.reported.trigger.notification',
	TRIGGER_NOTIFICATION_DOCUMENT_CREATED = 'fb.exchange.module.document.created.trigger.notification',
	TRIGGER_NOTIFICATION_DOCUMENT_UPDATED = 'fb.exchange.module.document.updated.trigger.notification',
	TRIGGER_NOTIFICATION_DOCUMENT_DELETED = 'fb.exchange.module.document.deleted.trigger.notification',

	// Conditions
	TRIGGER_CONDITION_DOCUMENT_REPORTED = 'fb.exchange.module.document.reported.trigger.condition',
	TRIGGER_CONDITION_DOCUMENT_CREATED = 'fb.exchange.module.document.created.trigger.condition',
	TRIGGER_CONDITION_DOCUMENT_UPDATED = 'fb.exchange.module.document.updated.trigger.condition',
	TRIGGER_CONDITION_DOCUMENT_DELETED = 'fb.exchange.module.document.deleted.trigger.condition',
}

export enum UiModuleRoutes {}
