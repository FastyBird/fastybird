export * from './dashboards/types';
export * from './tabs/types';
export * from './groups/types';
export * from './widgets/types';
export * from './widgets-display/types';
export * from './widgets-data-sources/types';

export interface IEntityMeta {
	source: string;
	entity: 'dashboard' | 'tab' | 'group' | 'widget' | 'display' | 'data-source';
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
