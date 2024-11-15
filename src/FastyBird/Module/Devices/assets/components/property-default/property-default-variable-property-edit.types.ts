import { FormItemRule } from 'element-plus';

import { IChannelProperty, IConnectorProperty, IDeviceProperty } from '../../models/types';

export interface IPropertyDefaultVariablePropertyEditProps {
	modelValue: string | number | boolean | null | Date | undefined;
	property: IConnectorProperty | IDeviceProperty | IChannelProperty;
	label?: string;
	disabled?: boolean;
	readonly?: boolean;
	rules?: FormItemRule[];
	options?: { label: string; value: string }[];
}
