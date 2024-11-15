import { StateTree, Store } from 'pinia';
import { StoreInjectionKey } from '../configuration';

import { ApplicationError } from '../errors';

export { registerChannelsStore } from './channels';
export { registerChannelsControlsStore } from './channels-controls';
export { registerChannelsPropertiesStore } from './channels-properties';
export { registerConnectorsStore } from './connectors';
export { registerConnectorsControlsStore } from './connectors-controls';
export { registerConnectorsPropertiesStore } from './connectors-properties';
export { registerDevicesStore } from './devices';
export { registerDevicesControlsStore } from './devices-controls';
export { registerDevicesPropertiesStore } from './devices-properties';

export class StoresManager {
	private stores: Map<StoreInjectionKey<string, any, any, any>, Store<string, any, any, any>> = new Map();

	public addStore<Id extends string = string, S extends StateTree = object, G = object, A = object>(
		key: StoreInjectionKey<Id, S, G, A>,
		store: Store<Id, S, G, A>
	): void {
		this.stores.set(key, store);
	}

	public getStore<Id extends string = string, S extends StateTree = object, G = object, A = object>(
		key: StoreInjectionKey<Id, S, G, A>
	): Store<Id, S, G, A> {
		if (!this.stores.has(key)) {
			throw new ApplicationError('Something went wrong, module is wrongly configured', null);
		}

		return this.stores.get(key) as Store<Id, S, G, A>;
	}
}
