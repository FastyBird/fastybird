import { App, InjectionKey } from 'vue';
import { Router } from 'vue-router';

import moduleRouter from '@/lib/router';

export interface IDevicesModuleOptions {
	router?: Router;
	meta: IDeviceModuleMeta;
	configuration: IDeviceModuleConfiguration;
}

export interface IDeviceModuleMeta {
	[key: string]: any;
}

export interface IDeviceModuleConfiguration {
	[key: string]: any;
}

export const metaKey: InjectionKey<IDeviceModuleMeta> = Symbol('devices-module_meta');
export const configurationKey: InjectionKey<IDeviceModuleConfiguration> = Symbol('devices-module_configuration');

export default {
	install: (app: App, options: IDevicesModuleOptions) => {
		if (typeof options.router === 'undefined') {
			throw new Error('Router instance is missing in module configuration');
		}

		moduleRouter(options.router);

		app.provide(metaKey, options.meta);
		app.provide(configurationKey, options.configuration);
	},
};

export * from '@/lib/components';
export * from '@/lib/composables';
export * from '@/lib/models';

export * from '@/types/devices-module';
