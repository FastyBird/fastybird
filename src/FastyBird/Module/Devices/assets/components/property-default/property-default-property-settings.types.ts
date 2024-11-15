import { IChannel, IChannelProperty, IConnector, IConnectorProperty, IDevice, IDeviceProperty } from '../../types';

export interface IPropertyDefaultPropertySettingsProps {
	connector?: IConnector;
	device?: IDevice;
	channel?: IChannel;
	property: IChannelProperty | IConnectorProperty | IDeviceProperty;
	title?: string;
}
