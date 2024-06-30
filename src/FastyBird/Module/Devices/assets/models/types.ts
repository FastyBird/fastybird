export * from './channels/types';
export * from './channels-controls/types';
export * from './channels-properties/types';
export * from './connectors/types';
export * from './connectors-controls/types';
export * from './connectors-properties/types';
export * from './controls/types';
export * from './devices/types';
export * from './devices-controls/types';
export * from './devices-properties/types';
export * from './properties/types';

export interface IEntityMeta {
	source: string;
	type: string;
	entity: 'connector' | 'device' | 'channel' | 'property' | 'control';
}

// STORE
// =====

export enum SemaphoreTypes {
	FETCHING = 'fetching',
	GETTING = 'getting',
	CREATING = 'creating',
	UPDATING = 'updating',
	DELETING = 'deleting',
}

// API RESPONSES
// =============

export interface IPlainRelation {
	id: string;
	type: { source: string; type?: string; parent?: string; entity: string };
}

export interface IErrorResponseJson {
	errors: IErrorResponseError[];
}

interface IErrorResponseError {
	code: string;
	status: string;
	title?: string;
	detail?: string;
}
