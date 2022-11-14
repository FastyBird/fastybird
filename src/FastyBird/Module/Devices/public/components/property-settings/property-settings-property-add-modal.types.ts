import { IConnector } from '@/models/connectors/types';
import { IDevice } from '@/models/devices/types';
import { IChannel } from '@/models/channels/types';
import { IChannelProperty } from '@/models/channels-properties/types';
import { IDeviceProperty } from '@/models/devices-properties/types';
import { IConnectorProperty } from '@/models/connectors-properties/types';

export interface IPropertySettingsPropertyAddModalProps {
	connector?: IConnector;
	device?: IDevice;
	channel?: IChannel;
	property: IChannelProperty | IDeviceProperty | IConnectorProperty;
}

export interface IConnectorListItem extends IConnector {
	disabled: boolean;
}

export interface IDeviceListItem extends IDevice {
	disabled: boolean;
}

export interface IChannelListItem extends IChannel {
	disabled: boolean;
}

export enum PropertySettingsPropertyAddModalViewTypes {
	SELECT_TYPE = 'selectType',
	NEW_PROPERTY = 'newProperty',
	MAPPED_PROPERTY = 'mappedProperty',
	SELECT_CONNECTOR = 'selectConnector',
	SELECT_DEVICE = 'selectDevice',
	SELECT_CHANNEL = 'selectChannel',
	SELECT_PARENT = 'selectParent',
}
