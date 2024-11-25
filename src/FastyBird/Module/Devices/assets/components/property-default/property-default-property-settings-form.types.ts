import { FormResultType, IChannel, IChannelProperty, IConnector, IConnectorProperty, IDevice, IDeviceProperty } from '../../types';

export interface IPropertyDefaultPropertySettingsFormProps {
	connector?: IConnector;
	device?: IDevice;
	channel?: IChannel;
	property: IChannelProperty | IDeviceProperty | IConnectorProperty;
	remoteFormSubmit?: boolean;
	remoteFormReset?: boolean;
	remoteFormResult?: FormResultType;
}
