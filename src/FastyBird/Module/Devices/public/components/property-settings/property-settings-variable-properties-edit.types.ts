import { IConnectorProperty } from '@/models/connectors-properties/types';
import { IDeviceProperty } from '@/models/devices-properties/types';
import { IChannelProperty } from '@/models/channels-properties/types';

export interface IPropertySettingsVariablePropertiesEditModel {
	id: string;
	value: string | undefined;
}

export interface IPropertySettingsVariablePropertiesEditProps {
	modelValue: IPropertySettingsVariablePropertiesEditModel[];
	properties: IConnectorProperty[] | IDeviceProperty[] | IChannelProperty[];
}
