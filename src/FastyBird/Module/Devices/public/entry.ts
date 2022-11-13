import { App, InjectionKey } from 'vue';
import { Router } from 'vue-router';

import moduleRouter from '@/router';
import { InstallFunction } from '@/types/devices-module';

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

export function createDevicesModule(): InstallFunction {
	const plugin: InstallFunction = {
		install(app: App, options: IDevicesModuleOptions): void {
			if (this.installed) {
				return;
			}
			this.installed = true;

			if (typeof options.router === 'undefined') {
				throw new Error('Router instance is missing in module configuration');
			}

			moduleRouter(options.router);

			app.provide(metaKey, options.meta);
			app.provide(configurationKey, options.configuration);
		},
	};

	return plugin;
}

export * from '@/components';
export * from '@/composables';
export * from '@/layouts';
export * from '@/models';
export * from '@/router';

export * from '@/types/devices-module';
