import { IRoutes } from '../types';

export function useRoutesNames(): { routeNames: IRoutes } {
	const routeNames: IRoutes = {
		root: 'triggers_module-root',

		triggers: 'triggers_module-triggers',
	};

	return {
		routeNames,
	};
}
