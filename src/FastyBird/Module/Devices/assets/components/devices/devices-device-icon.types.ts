import { IDevice } from '../../models/types';

export interface IDevicesDeviceIconProps {
	device: IDevice;
	withState?: boolean;
	size?: number | string;
}
