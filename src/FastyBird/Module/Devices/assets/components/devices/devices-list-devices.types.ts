import { DevicesFilter, IDeviceData } from '../../types';

export interface IDevicesListDevicesProps {
	items: IDeviceData[];
	allItems: IDeviceData[];
	totalRows: number;
	filters: DevicesFilter;
	paginateSize: number;
	paginatePage: number;
	sortDir: 'asc' | 'desc';
	loading: boolean;
}
