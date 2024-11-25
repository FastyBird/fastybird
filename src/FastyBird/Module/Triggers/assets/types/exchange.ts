export enum ActionRoutes {
	TRIGGER_CONTROL = 'fb.exchange.action.trigger.control',
}

export enum RoutingKeys {
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
