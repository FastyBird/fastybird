import { DBSchema, IDBPDatabase, openDB } from 'idb';
import { IChannelControlDatabaseRecord } from '../models/channels-controls/types';
import { IChannelPropertyDatabaseRecord } from '../models/channels-properties/types';
import { IChannelDatabaseRecord } from '../models/channels/types';
import { IConnectorControlDatabaseRecord } from '../models/connectors-controls/types';
import { IConnectorPropertyDatabaseRecord } from '../models/connectors-properties/types';
import { IConnectorDatabaseRecord } from '../models/connectors/types';
import { IDeviceControlDatabaseRecord } from '../models/devices-controls/types';
import { IDevicePropertyDatabaseRecord } from '../models/devices-properties/types';
import { IDeviceDatabaseRecord } from '../models/devices/types';

const DB_NAME = 'devices_module';
const DB_VERSION = 1;

export const DB_TABLE_CONNECTORS = 'connectors';
export const DB_TABLE_CONNECTORS_PROPERTIES = 'connectors_properties';
export const DB_TABLE_CONNECTORS_CONTROLS = 'connectors_controls';
export const DB_TABLE_DEVICES = 'devices';
export const DB_TABLE_DEVICES_PROPERTIES = 'devices_properties';
export const DB_TABLE_DEVICES_CONTROLS = 'devices_controls';
export const DB_TABLE_CHANNELS = 'channels';
export const DB_TABLE_CHANNELS_PROPERTIES = 'channels_properties';
export const DB_TABLE_CHANNELS_CONTROLS = 'channels_controls';

type StoreName =
	| typeof DB_TABLE_CONNECTORS
	| typeof DB_TABLE_CONNECTORS_PROPERTIES
	| typeof DB_TABLE_CONNECTORS_CONTROLS
	| typeof DB_TABLE_DEVICES
	| typeof DB_TABLE_DEVICES_PROPERTIES
	| typeof DB_TABLE_DEVICES_CONTROLS
	| typeof DB_TABLE_CHANNELS
	| typeof DB_TABLE_CHANNELS_PROPERTIES
	| typeof DB_TABLE_CHANNELS_CONTROLS;

interface StorageDbSchema extends DBSchema {
	[DB_TABLE_CONNECTORS]: {
		key: string;
		value: IConnectorDatabaseRecord;
	};
	[DB_TABLE_CONNECTORS_PROPERTIES]: {
		key: string;
		value: IConnectorPropertyDatabaseRecord;
	};
	[DB_TABLE_CONNECTORS_CONTROLS]: {
		key: string;
		value: IConnectorControlDatabaseRecord;
	};
	[DB_TABLE_DEVICES]: {
		key: string;
		value: IDeviceDatabaseRecord;
	};
	[DB_TABLE_DEVICES_PROPERTIES]: {
		key: string;
		value: IDevicePropertyDatabaseRecord;
	};
	[DB_TABLE_DEVICES_CONTROLS]: {
		key: string;
		value: IDeviceControlDatabaseRecord;
	};
	[DB_TABLE_CHANNELS]: {
		key: string;
		value: IChannelDatabaseRecord;
	};
	[DB_TABLE_CHANNELS_PROPERTIES]: {
		key: string;
		value: IChannelPropertyDatabaseRecord;
	};
	[DB_TABLE_CHANNELS_CONTROLS]: {
		key: string;
		value: IChannelControlDatabaseRecord;
	};
}

type DatabaseRecord =
	| IConnectorDatabaseRecord
	| IConnectorPropertyDatabaseRecord
	| IConnectorControlDatabaseRecord
	| IDeviceDatabaseRecord
	| IDevicePropertyDatabaseRecord
	| IDeviceControlDatabaseRecord
	| IChannelDatabaseRecord
	| IChannelPropertyDatabaseRecord
	| IChannelControlDatabaseRecord;

export const initDB = async (): Promise<IDBPDatabase<StorageDbSchema>> => {
	return openDB(DB_NAME, DB_VERSION, {
		upgrade(db): void {
			// List all store names you expect
			const storeNames: StoreName[] = [
				DB_TABLE_CONNECTORS,
				DB_TABLE_CONNECTORS_PROPERTIES,
				DB_TABLE_CONNECTORS_CONTROLS,
				DB_TABLE_DEVICES,
				DB_TABLE_DEVICES_PROPERTIES,
				DB_TABLE_DEVICES_CONTROLS,
				DB_TABLE_CHANNELS,
				DB_TABLE_CHANNELS_PROPERTIES,
				DB_TABLE_CHANNELS_CONTROLS,
			];

			// Create stores if they do not exist
			storeNames.forEach((storeName) => {
				if (!db.objectStoreNames.contains(storeName)) {
					db.createObjectStore(storeName, { keyPath: 'id' });
				}
			});
		},
	});
};

export const doesStoreExist = async (storeName: StoreName): Promise<boolean> => {
	const db = await initDB();

	return db.objectStoreNames.contains(storeName);
};

export const addRecord = async <T extends DatabaseRecord>(record: T, storeName: StoreName): Promise<void> => {
	const db = await initDB();
	const tx = db.transaction(storeName, 'readwrite');

	await tx.objectStore(storeName).put(record);

	await tx.done;
};

export const getRecord = async <T extends DatabaseRecord>(id: string, storeName: StoreName): Promise<T | undefined> => {
	const db = await initDB();

	return (await db.transaction(storeName).objectStore(storeName).get(id)) as T | undefined;
};

export const getAllRecords = async <T extends DatabaseRecord>(storeName: StoreName): Promise<T[]> => {
	const db = await initDB();

	return (await db.transaction(storeName).objectStore(storeName).getAll()) as T[];
};

export const removeRecord = async (id: string, storeName: StoreName): Promise<void> => {
	const db = await initDB();
	const tx = db.transaction(storeName, 'readwrite');

	await tx.objectStore(storeName).delete(id);

	await tx.done;
};
