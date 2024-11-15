import { IChannel, IChannelProperty, IConnector, IConnectorProperty, IDevice, IDeviceProperty } from '../../types';

export interface IPropertyDefaultPropertySettingsEditProps {
	connector?: IConnector;
	device?: IDevice;
	channel?: IChannel;
	property: IChannelProperty | IDeviceProperty | IConnectorProperty;
}
