import { IChannel, IChannelProperty, IConnectorProperty, IDevice, IDeviceProperty } from '@/models/types';

export interface IPropertyActorProps {
	device?: IDevice;
	channel?: IChannel;
	property: IChannelProperty | IDeviceProperty | IConnectorProperty;
}
