import { IChannel, IConnector, IConnectorPlugin, IDevice } from '../types';

export interface IViewChannelDetailProps {
	id: IChannel['id'];
	deviceId: IDevice['id'];
	plugin?: IConnectorPlugin['type'];
	connectorId?: IConnector['id'];
}
