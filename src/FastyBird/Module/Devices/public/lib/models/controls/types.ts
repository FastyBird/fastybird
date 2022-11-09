import { TJsonaModel } from 'jsona/lib/JsonaTypes';

// STORE MODELS
// ============

export interface IControl {
	id: string;
	type: { source: string; parent: string; entity: string };

	draft: boolean;

	name: string;

	// Relations
	relationshipNames: string[];
}

// STORE DATA FACTORIES
// ====================

export interface IControlRecordFactoryPayload {
	id?: string;
	type: { source: string; parent?: string; entity?: string };

	draft?: boolean;

	name: string;

	// Relations
	relationshipNames?: string[];
}

// STORE ACTIONS
// =============

export interface IControlsAddActionPayload {
	id?: string;
	type: { source: string; parent?: string; entity?: string };

	draft?: boolean;

	data: {
		name: string;
	};
}

// API RESPONSE MODELS
// ===================

export interface IControlResponseModel extends TJsonaModel {
	id: string;
	type: { source: string; parent: string; entity: string };

	name: string;

	// Relations
	relationshipNames: string[];
}
