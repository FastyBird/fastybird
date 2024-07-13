import { Pinia } from 'pinia';
import { Plugin } from 'vue';
import { I18n } from 'vue-i18n';
import { Router } from 'vue-router';
import { Client } from '@fastybird/vue-wamp-v1';

export type InstallFunction = Plugin & { installed?: boolean };

export interface IUiModuleOptions {
	router?: Router;
	meta: IUiModuleMeta;
	configuration: IUiModuleConfiguration;
	store: Pinia;
	wsClient?: Client;
	i18n?: I18n;
}

export interface IUiModuleMeta {
	author: string;
	website: string;
	version: string;
	[key: string]: any;
}

export interface IUiModuleConfiguration {
	injectionKeys: {
		eventBusInjectionKey?: symbol | string;
	};
	[key: string]: any;
}

export interface IRoutes {
	root: string;

	dashboards: string;
	dashboardDetail: string;
	dashboardSettings: string;
	dashboardSettingsAddWidget: string;
	dashboardSettingsEditWidget: string;

	groups: string;
	groupDetail: string;
	groupSettings: string;
	groupSettingsAddWidget: string;
	groupSettingsEditWidget: string;

	widgets: string;
	widgetDetail: string;
	widgetSettings: string;
}

export enum FormResultTypes {
	NONE = 'none',
	WORKING = 'working',
	ERROR = 'error',
	OK = 'ok',
}

export type FormResultType = FormResultTypes.NONE | FormResultTypes.WORKING | FormResultTypes.ERROR | FormResultTypes.OK;
