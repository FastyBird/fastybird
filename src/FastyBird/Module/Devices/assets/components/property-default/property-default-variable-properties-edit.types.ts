import { FormItemRule } from 'element-plus';

import { IChannelProperty, IConnectorProperty, IDeviceProperty } from '../../types';

export type IPropertyDefaultVariablePropertiesEditModel = {
	[key: IConnectorProperty['id'] | IDeviceProperty['id'] | IChannelProperty['id']]: string | number | boolean | Date | null;
};

export interface IPropertyDefaultVariablePropertiesEditProps {
	modelValue: IPropertyDefaultVariablePropertiesEditModel;
	properties: IConnectorProperty[] | IDeviceProperty[] | IChannelProperty[];
	labels?: { [key: IConnectorProperty['id'] | IDeviceProperty['id'] | IChannelProperty['id']]: string };
	readonly?: { [key: IConnectorProperty['id'] | IDeviceProperty['id'] | IChannelProperty['id']]: boolean };
	disabled?: { [key: IConnectorProperty['id'] | IDeviceProperty['id'] | IChannelProperty['id']]: boolean };
	rules?: { [key: IConnectorProperty['id'] | IDeviceProperty['id'] | IChannelProperty['id']]: FormItemRule[] };
}
