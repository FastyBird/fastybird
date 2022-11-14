import { IConnector } from '@/models/connectors/types';
import { IDevice } from '@/models/devices/types';
import { IChannel } from '@/models/channels/types';
import { IChannelProperty } from '@/models/channels-properties/types';
import { IConnectorProperty } from '@/models/connectors-properties/types';
import { IDeviceProperty } from '@/models/devices-properties/types';

export interface IPropertyDefaultPropertyProps {
	connector?: IConnector;
	device?: IDevice;
	channel?: IChannel;
	property: IChannelProperty | IConnectorProperty | IDeviceProperty;
}
