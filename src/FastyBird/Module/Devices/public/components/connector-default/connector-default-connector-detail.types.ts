import { IConnector } from '@/models/connectors/types';
import { IConnectorProperty } from '@/models/connectors-properties/types';
import { IConnectorControl } from '@/models/connectors-controls/types';
import { IDeviceData } from '@/types';

export interface IConnectorsConnectorDetailDefaultProps {
	connectorData: {
		connector: IConnector;
		properties: IConnectorProperty[];
		controls: IConnectorControl[];
		devices: IDeviceData[];
	};
}
