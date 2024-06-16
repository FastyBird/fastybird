import { IConnector, IConnectorControl, IConnectorProperty } from '../../models/types';
import { IDeviceData } from '../../types';

export interface IDeviceDefaultChannelPropertyProps {
	connector: IConnector;
	connectorControls: IConnectorControl[];
	connectorProperties: IConnectorProperty[];
	deviceData: IDeviceData;
	editMode?: boolean;
}
