import { IConnector } from '@/models/connectors/types';
import { IDeviceData } from '@/types';

export interface IDeviceDefaultChannelPropertyProps {
	connector: IConnector;
	deviceData: IDeviceData;
}
