import { DataType } from '@fastybird/metadata-library';
import { IConnector } from '@/models/connectors/types';
import { IDevice } from '@/models/devices/types';
import { IChannel } from '@/models/channels/types';
import { IChannelProperty } from '@/models/channels-properties/types';
import { IDeviceProperty } from '@/models/devices-properties/types';
import { IConnectorProperty } from '@/models/connectors-properties/types';
import { FbFormResultTypes } from '@fastybird/web-ui-library';

export interface IPropertySettingsPropertyFormForm {
	identifier: string;
	name: string | null;
	settable: boolean;
	queryable: boolean;
	dataType: DataType;
	unit: string | null;
	invalid: string | null;
	numberOfDecimals: number | null;
	format: string | null;
}

export interface IPropertySettingsPropertyFormProps {
	connector?: IConnector;
	device?: IDevice;
	channel?: IChannel;
	property: IChannelProperty | IDeviceProperty | IConnectorProperty;
	remoteFormSubmit?: boolean;
	remoteFormReset?: boolean;
	remoteFormResult?: FbFormResultTypes;
}
