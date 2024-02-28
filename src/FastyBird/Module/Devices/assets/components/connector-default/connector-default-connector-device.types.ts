import { IConnector } from '../../models/types';
import { IDeviceData } from '../../types';

export interface IDeviceDefaultChannelPropertyProps {
	connector: IConnector;
	deviceData: IDeviceData;
}
