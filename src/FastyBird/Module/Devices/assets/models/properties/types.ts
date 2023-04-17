import { TJsonaModel } from 'jsona/lib/JsonaTypes';

import { DataType, PropertyCategory } from '@fastybird/metadata-library';

// COMMANDS
// ========

export enum PropertyCommandState {
	SENDING = 'sending',
	COMPLETED = 'completed',
}

export enum PropertyCommandResult {
	OK = 'ok',
	ERR = 'err',
}

// STORE MODELS
// ============

export interface IProperty {
	id: string;
	type: { source: string; type: string; parent: string; entity: string };

	draft: boolean;

	category: PropertyCategory;
	identifier: string;
	name: string | null;
	settable: boolean;
	queryable: boolean;
	dataType: DataType;
	unit: string | null;
	format: string[] | (string | null)[][] | (number | null)[] | null;
	invalid: string | number | null;
	scale: number | null;
	step: number | null;

	// Static property
	value: string | number | boolean | Date | null;

	/* Dynamic property start */
	actualValue: string | number | boolean | Date | null;
	expectedValue: string | number | boolean | Date | null;
	pending: boolean | Date | null;

	command: PropertyCommandState | null;
	lastResult: PropertyCommandResult | null;
	backupValue: string | null;
	/* Dynamic property end */

	// Relations
	relationshipNames: string[];
}

// STORE DATA FACTORIES
// ====================

export interface IPropertyRecordFactoryPayload {
	id?: string;
	type: { source: string; type: string; parent?: string; entity?: string };

	draft?: boolean;

	category: PropertyCategory;
	identifier: string;
	name?: string | null;
	settable?: boolean;
	queryable?: boolean;
	dataType: DataType;
	unit?: string | null;
	format?: string[] | (string | null)[][] | (number | null)[] | null;
	invalid?: string | number | null;
	scale?: number | null;
	step?: number | null;

	// Static property
	value?: string | number | boolean | Date | null;

	/* Dynamic property start */
	actualValue?: string | number | boolean | Date | null;
	expectedValue?: string | number | boolean | Date | null;
	pending?: boolean | Date | null;

	command?: PropertyCommandState | null;
	lastResult?: PropertyCommandResult | null;
	backupValue?: string | null;
	/* Dynamic property end */

	// Relations
	relationshipNames?: string[];
}

// STORE ACTIONS
// =============

export interface IPropertiesAddActionPayload {
	id?: string;
	type: { source: string; type: string; parent?: string; entity?: string };

	draft?: boolean;

	data: {
		identifier: string;
		name?: string | null;
		settable?: boolean;
		queryable?: boolean;
		dataType: DataType;
		unit?: string | null;
		format?: string[] | (string | null)[][] | (number | null)[] | null;
		invalid?: string | number | null;
		scale?: number | null;
		step?: number | null;

		// Static property
		value?: string | number | boolean | Date | null;
	};
}

export interface IPropertiesEditActionPayload {
	id: string;

	data: {
		identifier?: string;
		name?: string | null;
		settable?: boolean;
		queryable?: boolean;
		dataType?: DataType;
		unit?: string | null;
		format?: string[] | (string | null)[][] | (number | null)[] | null;
		invalid?: string | number | null;
		scale?: number | null;
		step?: number | null;

		// Static property
		value?: string | number | boolean | Date | null;

		/* Dynamic property start */
		actualValue?: string | number | boolean | Date | null;
		expectedValue?: string | number | boolean | Date | null;
		pending?: boolean | Date | null;

		command?: PropertyCommandState | null;
		lastResult?: PropertyCommandResult | null;
		backupValue?: string | null;
		/* Dynamic property end */
	};
}

export interface IPropertiesSetStateActionPayload {
	id: string;

	data: {
		actualValue?: string | number | boolean | Date | null;
		expectedValue?: string | number | boolean | Date | null;
		pending?: boolean | Date | null;

		command?: PropertyCommandState | null;
		lastResult?: PropertyCommandResult | null;
		backupValue?: string | number | boolean | Date | null;
	};
}

// API RESPONSE MODELS
// ===================

export interface IPropertyResponseModel extends TJsonaModel {
	id: string;
	type: { source: string; type: string; parent: string; entity: string };

	category: PropertyCategory;
	identifier: string;
	name: string | null;
	settable: boolean;
	queryable: boolean;
	dataType: DataType;
	unit: string | null;
	format: string[] | (string | null)[][] | (number | null)[] | null;
	invalid: string | number | null;
	scale: number | null;
	step: number | null;

	value: string | number | boolean | Date | null;

	actualValue: string | number | boolean | Date | null;
	expectedValue: string | number | boolean | Date | null;
	pending: boolean | Date | null;

	// Relations
	relationshipNames: string[];
}
