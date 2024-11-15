import { IChannel, IConnector, IConnectorPlugin, IDevice } from '../types';

export interface IViewConnectorSettingsProps {
	plugin: IConnectorPlugin['type'];
	id?: IConnector['id'];
	deviceId?: IDevice['id'];
	channelId?: IChannel['id'];
}
