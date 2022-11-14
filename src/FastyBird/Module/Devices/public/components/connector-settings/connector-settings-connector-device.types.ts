import { IConnector } from '@/models/connectors/types';
import { IDeviceData } from '@/types';

export interface IConnectorSettingsConnectorPropertyProps {
	connector: IConnector;
	deviceData: IDeviceData;
}

export enum ConnectorSettingsConnectorDeviceViewTypes {
	NONE = 'none',
	REMOVE = 'remove',
}
