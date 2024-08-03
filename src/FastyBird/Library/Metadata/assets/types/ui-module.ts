export interface DashboardDocument {
	id: string;
	source: string;
	identifier: string;
	name: string | null;
	comment: string | null;
	priority: number;
	tabs: TabDocument['id'][];
	owner: string | null;
	createdAt: Date | null;
	updatedAt: Date | null;
}

export interface TabDocument {
	id: string;
	source: string;
	identifier: string;
	name: string | null;
	comment: string | null;
	priority: number;
	dashboard: DashboardDocument['id'];
	widgets: WidgetDocument['id'][];
	owner: string | null;
	createdAt: Date | null;
	updatedAt: Date | null;
}

export interface GroupDocument {
	id: string;
	source: string;
	identifier: string;
	name: string | null;
	comment: string | null;
	priority: number;
	widgets: WidgetDocument['id'][];
	owner: string | null;
	createdAt: Date | null;
	updatedAt: Date | null;
}

export interface WidgetDocument {
	id: string;
	type: string;
	source: string;
	identifier: string;
	name: string | null;
	comment: string | null;
	display: WidgetDisplayDocument['id'];
	data_sources: WidgetDataSourceDocument['id'][];
	tabs: TabDocument['id'][];
	groups: GroupDocument['id'][];
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
	widget: WidgetDocument['id'];
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
	widget: WidgetDocument['id'];
	owner: string | null;
	createdAt: Date | null;
	updatedAt: Date | null;
}
