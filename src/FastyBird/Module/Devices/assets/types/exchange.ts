export enum ActionRoutes {
	CONNECTOR_CONTROL = 'fb.exchange.action.connector.control',
	CONNECTOR_PROPERTY = 'fb.exchange.action.connector.property',
	DEVICE_CONTROL = 'fb.exchange.action.device.control',
	DEVICE_PROPERTY = 'fb.exchange.action.device.property',
	CHANNEL_CONTROL = 'fb.exchange.action.channel.control',
	CHANNEL_PROPERTY = 'fb.exchange.action.channel.property',
}

export enum RoutingKeys {
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

export enum ControlName {
	CONFIGURE = 'configure',
	RESET = 'reset',
	FACTORY_RESET = 'factory_reset',
	REBOOT = 'reboot',
	TRIGGER = 'trigger',
}

export enum PropertyAction {
	SET = 'set',
	GET = 'get',
	REPORT = 'report',
}

export enum ControlAction {
	SET = 'set',
}

export enum ExchangeCommand {
	SET = 'set',
	GET = 'get',
	REPORT = 'report',
}
