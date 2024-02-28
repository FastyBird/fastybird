import { IChannelProperty, IConnectorProperty, IDeviceProperty } from '../../models/types';

export interface IPropertiesPropertyIconProps {
	property: IDeviceProperty | IChannelProperty | IConnectorProperty;
}
