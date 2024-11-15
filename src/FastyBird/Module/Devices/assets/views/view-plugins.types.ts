import { IChannel, IConnector, IConnectorPlugin, IDevice } from '../types';

export interface IViewPluginsProps {
	plugin?: IConnectorPlugin['type'];
	id?: IConnector['id'];
	deviceId?: IDevice['id'];
	channelId?: IChannel['id'];
}
