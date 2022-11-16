import { Plugin } from 'vue';
import { Router } from 'vue-router';

export type InstallFunction = Plugin & { installed?: boolean };

export interface ITriggersModuleOptions {
	router?: Router;
	meta: ITriggersModuleMeta;
	configuration: ITriggersModuleConfiguration;
}

export interface ITriggersModuleMeta {
	[key: string]: any;
}

export interface ITriggersModuleConfiguration {
	[key: string]: any;
}

export interface IRoutes {
	root: string;

	triggers: string;
}
