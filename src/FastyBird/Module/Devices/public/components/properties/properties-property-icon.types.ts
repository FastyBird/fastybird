import { IDeviceProperty } from '@/models/devices-properties/types';
import { IChannelProperty } from '@/models/channels-properties/types';
import { IConnectorProperty } from '@/models/connectors-properties/types';

export interface IPropertiesPropertyIconProps {
	property: IDeviceProperty | IChannelProperty | IConnectorProperty;
}
