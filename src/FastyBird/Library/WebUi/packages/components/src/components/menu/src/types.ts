import type { RouteLocationRaw } from 'vue-router';

export interface MenuItemRegistered {
	index: string;
	indexPath: string[];
	active: boolean;
}

export interface MenuItemClicked {
	index: string;
	indexPath: string[];
	route?: RouteLocationRaw;
}
