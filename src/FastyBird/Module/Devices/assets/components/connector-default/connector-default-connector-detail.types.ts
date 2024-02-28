import { IConnector, IConnectorControl, IConnectorProperty } from '../../models/types';
import { IDeviceData } from '../../types';

export interface IConnectorsConnectorDetailDefaultProps {
	connectorData: {
		connector: IConnector;
		properties: IConnectorProperty[];
		controls: IConnectorControl[];
		devices: IDeviceData[];
	};
}
