import { IConnectorData } from '../../types';

export interface IConnectorsConnectorDetailDefaultProps {
	loading: boolean;
	devicesLoading: boolean;
	connectorData: IConnectorData;
	editMode?: boolean;
}
