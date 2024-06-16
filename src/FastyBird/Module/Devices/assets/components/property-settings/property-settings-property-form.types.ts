import { DataType } from '@fastybird/metadata-library';

import { IChannel, IChannelProperty, IConnector, IConnectorProperty, IDevice, IDeviceProperty } from '../../models/types';
import { FormResultTypes } from '../../types';

export interface IPropertySettingsPropertyFormForm {
	identifier: string;
	name: string | null;
	settable: boolean;
	queryable: boolean;
	dataType: DataType;
	unit: string | null;
	invalid: string | null;
	scale: number | null;
	format: string | null;
}

export interface IPropertySettingsPropertyFormProps {
	connector?: IConnector;
	device?: IDevice;
	channel?: IChannel;
	property: IChannelProperty | IDeviceProperty | IConnectorProperty;
	remoteFormSubmit?: boolean;
	remoteFormReset?: boolean;
	remoteFormResult?: FormResultTypes;
}
