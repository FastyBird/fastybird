export interface DashboardDocument {
	id: string;
	source: string;
	identifier: string;
	name: string;
	comment: string | null;
	priority: number;
	widgets: string[];
	owner: string | null;
	createdAt: Date | null;
	updatedAt: Date | null;
}

export interface GroupDocument {
	id: string;
	source: string;
	identifier: string;
	name: string;
	comment: string | null;
	priority: number;
	widgets: string[];
	owner: string | null;
	createdAt: Date | null;
	updatedAt: Date | null;
}

export interface WidgetDocument {
	id: string;
	type: string;
	source: string;
	identifier: string;
	name: string;
	comment: string | null;
	display: string;
	data_sources: string[];
	dashboard: string[];
	groups: string[];
	owner: string | null;
	createdAt: Date | null;
	updatedAt: Date | null;
}

export interface WidgetDisplayDocument {
	id: string;
	type: string;
	source: string;
	identifier: string;
	params: object;
	widget: string;
	owner: string | null;
	createdAt: Date | null;
	updatedAt: Date | null;
}

export interface WidgetDataSourceDocument {
	id: string;
	type: string;
	source: string;
	identifier: string;
	params: object;
	widget: string;
	owner: string | null;
	createdAt: Date | null;
	updatedAt: Date | null;
}
