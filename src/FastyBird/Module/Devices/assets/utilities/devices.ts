import { IDevicesFilter, SimpleStateFilter } from '../types';

export const defaultDevicesFilter: IDevicesFilter = {
	search: '',
	state: SimpleStateFilter.ALL,
	states: [],
	plugins: [],
	connectors: [],
};
