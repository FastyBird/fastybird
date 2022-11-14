import { IDevice } from '@/models/devices/types';
import { IChannel } from '@/models/channels/types';
import { IChannelProperty } from '@/models/channels-properties/types';
import { IDeviceProperty } from '@/models/devices-properties/types';
import { IConnectorProperty } from '@/models/connectors-properties/types';

export interface IPropertyActorProps {
	device?: IDevice;
	channel?: IChannel;
	property: IChannelProperty | IDeviceProperty | IConnectorProperty;
}
