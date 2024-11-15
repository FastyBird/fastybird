import { IConnector, IConnectorPlugin, IDevicesFilter } from '../../types';

export interface IDevicesListAdjustProps {
	plugins: IConnectorPlugin[];
	connectors: IConnector[];
	filters: IDevicesFilter;
}
