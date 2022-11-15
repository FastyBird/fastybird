import { IConnector } from '@/models/connectors/types';

export interface IViewConnectorDetailProps {
	id: string;
	deviceId?: string;
	channelId?: string;
	connectors: IConnector[];
}

export enum ViewConnectorDetailViewTypes {
	NONE = 'none',
	REMOVE = 'remove',
}
