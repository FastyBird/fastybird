import { App } from 'vue';
import get from 'lodash/get';
import defaultsDeep from 'lodash/defaultsdeep';

import { wampClient } from '@fastybird/vue-wamp-v1';
import { ModulePrefix } from '@fastybird/metadata-library';

import moduleRouter from './router';
import { IDevicesModuleOptions, InstallFunction } from './types';
import { configurationKey, metaKey } from './configuration';
import {
	useChannelControls,
	useChannelProperties,
	useChannels,
	useConnectorControls,
	useConnectorProperties,
	useConnectors,
	useDeviceControls,
	useDeviceProperties,
	useDevices,
} from './models';
import { useFlashMessage } from './composables';
import locales from './locales';

import 'virtual:uno.css';

export function createDevicesModule(): InstallFunction {
	return {
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

			wampClient.subscribe(`/${ModulePrefix.MODULE_DEVICES}/v1/exchange`, onWsMessage);

			for (const [locale, translations] of Object.entries(locales)) {
				const currentMessages = options.i18n?.global.getLocaleMessage(locale);
				const mergedMessages = defaultsDeep(currentMessages, translations);

				options.i18n?.global.setLocaleMessage(locale, mergedMessages);
			}
		},
	};
}

const onWsMessage = (data: string): void => {
	const flashMessage = useFlashMessage();

	const body = JSON.parse(data);

	const stores = [
		useChannels(),
		useChannelControls(),
		useChannelProperties(),
		useConnectors(),
		useConnectorControls(),
		useConnectorProperties(),
		useDevices(),
		useDeviceControls(),
		useDeviceProperties(),
	];

	if (
		Object.prototype.hasOwnProperty.call(body, 'routing_key') &&
		Object.prototype.hasOwnProperty.call(body, 'source') &&
		Object.prototype.hasOwnProperty.call(body, 'data')
	) {
		stores.forEach((store) => {
			if (Object.prototype.hasOwnProperty.call(store, 'socketData')) {
				store
					.socketData({
						source: get(body, 'source'),
						routingKey: get(body, 'routing_key'),
						data: JSON.stringify(get(body, 'data')),
					})
					.catch((e): void => {
						if (get(e, 'exception', null) !== null) {
							flashMessage.exception(get(e, 'exception', null), 'Error parsing exchange data');
						} else {
							flashMessage.error('Error parsing exchange data');
						}
					});
			}
		});
	}
};

export * from './configuration';
export * from './components';
export * from './composables';
export * from './models';
export * from './router';

export * from './types';
