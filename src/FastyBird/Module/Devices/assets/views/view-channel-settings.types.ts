import { IChannel } from '../models/channels/types';
import { IDevice } from '../models/devices/types';
import { IConnector, IConnectorPlugin } from '../types';

export interface IViewChannelSettingsProps {
	id?: IChannel['id'];
	deviceId: IDevice['id'];
	plugin?: IConnectorPlugin['type'];
	connectorId?: IConnector['id'];
}
