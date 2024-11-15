import { IChannel, IConnector, IConnectorPlugin, IDevice } from '../types';

export interface IViewDeviceDetailProps {
	id: IDevice['id'];
	plugin?: IConnectorPlugin['type'];
	connectorId?: IConnector['id'];
	channelId?: IChannel['id'];
}
