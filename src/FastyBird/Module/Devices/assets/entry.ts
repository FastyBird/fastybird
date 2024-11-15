import { App } from 'vue';
import get from 'lodash.get';
import defaultsDeep from 'lodash.defaultsdeep';

import { wampClient } from '@fastybird/vue-wamp-v1';
import { ModulePrefix } from '@fastybird/metadata-library';

import {
	registerChannelsStore,
	registerChannelsControlsStore,
	registerChannelsPropertiesStore,
	registerConnectorsStore,
	registerConnectorsControlsStore,
	registerConnectorsPropertiesStore,
	registerDevicesStore,
	registerDevicesControlsStore,
	registerDevicesPropertiesStore,
	StoresManager,
} from './models';
import moduleRouter from './router';
import { IDevicesModuleOptions, InstallFunction } from './types';
import {
	channelControlsStoreKey,
	channelPropertiesStoreKey,
	channelsStoreKey,
	configurationKey,
	connectorControlsStoreKey,
	connectorPropertiesStoreKey,
	connectorsStoreKey,
	deviceControlsStoreKey,
	devicePropertiesStoreKey,
	devicesStoreKey,
	metaKey,
} from './configuration';
import { useFlashMessage } from './composables';
import locales from './locales';

import 'virtual:uno.css';

export const storesManager = new StoresManager();

export default function createDevicesModule(): InstallFunction {
	return {
		async install(app: App, options: IDevicesModuleOptions): Promise<void> {
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

			wampClient.subscribe(`/${ModulePrefix.DEVICES}/v1/exchange`, onWsMessage);

			for (const [locale, translations] of Object.entries(locales)) {
				const currentMessages = options.i18n?.global.getLocaleMessage(locale);
				const mergedMessages = defaultsDeep(currentMessages, { devicesModule: translations });

				options.i18n?.global.setLocaleMessage(locale, mergedMessages);
			}

			const channelsStore = registerChannelsStore(options.store);
			const channelControlsStore = registerChannelsControlsStore(options.store);
			const channelPropertiesStore = registerChannelsPropertiesStore(options.store);
			const connectorsStore = registerConnectorsStore(options.store);
			const connectorControlsStore = registerConnectorsControlsStore(options.store);
			const connectorPropertiesStore = registerConnectorsPropertiesStore(options.store);
			const devicesStore = registerDevicesStore(options.store);
			const deviceControlsStore = registerDevicesControlsStore(options.store);
			const devicePropertiesStore = registerDevicesPropertiesStore(options.store);

			app.provide(channelsStoreKey, channelsStore);
			storesManager.addStore(channelsStoreKey, channelsStore);
			app.provide(channelControlsStoreKey, channelControlsStore);
			storesManager.addStore(channelControlsStoreKey, channelControlsStore);
			app.provide(channelPropertiesStoreKey, channelPropertiesStore);
			storesManager.addStore(channelPropertiesStoreKey, channelPropertiesStore);
			app.provide(connectorsStoreKey, connectorsStore);
			storesManager.addStore(connectorsStoreKey, connectorsStore);
			app.provide(connectorControlsStoreKey, connectorControlsStore);
			storesManager.addStore(connectorControlsStoreKey, connectorControlsStore);
			app.provide(connectorPropertiesStoreKey, connectorPropertiesStore);
			storesManager.addStore(connectorPropertiesStoreKey, connectorPropertiesStore);
			app.provide(devicesStoreKey, devicesStore);
			storesManager.addStore(devicesStoreKey, devicesStore);
			app.provide(deviceControlsStoreKey, deviceControlsStore);
			storesManager.addStore(deviceControlsStoreKey, deviceControlsStore);
			app.provide(devicePropertiesStoreKey, devicePropertiesStore);
			storesManager.addStore(devicePropertiesStoreKey, devicePropertiesStore);
		},
	};
}

const onWsMessage = (data: string): void => {
	const flashMessage = useFlashMessage();

	const body = JSON.parse(data);

	const stores = [
		storesManager.getStore(channelsStoreKey),
		storesManager.getStore(channelControlsStoreKey),
		storesManager.getStore(channelPropertiesStoreKey),
		storesManager.getStore(connectorsStoreKey),
		storesManager.getStore(connectorControlsStoreKey),
		storesManager.getStore(connectorPropertiesStoreKey),
		storesManager.getStore(devicesStoreKey),
		storesManager.getStore(deviceControlsStoreKey),
		storesManager.getStore(devicePropertiesStoreKey),
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
export * from './router';

export * from './types';
