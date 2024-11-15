import { computed } from 'vue';
import { useRoute } from 'vue-router';

import { useRoutesNames } from '../composables';

import { UseConnectorRoutes } from './types';

export const useConnectorRoutes = (): UseConnectorRoutes => {
	const route = useRoute();

	const routeNames = useRoutesNames();

	const isDetailRoute = computed<boolean>((): boolean => route.name === routeNames.connectorDetail);

	const isSettingsRoute = computed<boolean>((): boolean => route.name === routeNames.connectorCreate || route.name === routeNames.connectorSettings);

	const isConnectorRoute = computed<boolean>(
		(): boolean =>
			route.matched.find((matched) => {
				return matched.name === routeNames.connectorCreate || matched.name === routeNames.connectorDetail;
			}) !== undefined
	);

	return {
		isDetailRoute,
		isSettingsRoute,
		isConnectorRoute,
	};
};
