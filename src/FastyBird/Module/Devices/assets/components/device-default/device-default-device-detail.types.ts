import { IDeviceData } from '../../types';

export interface IDevicesDeviceDetailDefaultProps {
	loading: boolean;
	channelsLoading: boolean;
	deviceData: IDeviceData;
	editMode?: boolean;
}
