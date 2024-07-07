export * from './account/types';
export * from './accounts/types';
export * from './emails/types';
export * from './identities/types';
export * from './roles/types';
export * from './session/types';

export interface IEntityMeta {
	source: string;
	entity: 'account' | 'email' | 'identity' | 'role' | 'session';
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
	type: { source: string; entity: string };
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
