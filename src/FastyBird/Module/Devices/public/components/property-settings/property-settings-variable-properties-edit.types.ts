import { IChannelProperty, IConnectorProperty, IDeviceProperty } from '@/models/types';

export interface IPropertySettingsVariablePropertiesEditModel {
	id: string;
	value: string | undefined;
}

export interface IPropertySettingsVariablePropertiesEditProps {
	modelValue: IPropertySettingsVariablePropertiesEditModel[];
	properties: IConnectorProperty[] | IDeviceProperty[] | IChannelProperty[];
}
