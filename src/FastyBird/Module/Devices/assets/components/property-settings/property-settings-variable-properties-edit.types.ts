import { IChannelProperty, IConnectorProperty, IDeviceProperty } from '../../models/types';

export interface IPropertySettingsVariablePropertiesEditModel {
	id: string;
	value: string | number | boolean | Date | null | undefined;
}

export interface IPropertySettingsVariablePropertiesEditProps {
	modelValue: IPropertySettingsVariablePropertiesEditModel[];
	properties: IConnectorProperty[] | IDeviceProperty[] | IChannelProperty[];
}
