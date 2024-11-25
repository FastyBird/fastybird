import { computed } from 'vue';
import { useRoute } from 'vue-router';

import { UseDeviceRoutes } from './types';
import { useRoutesNames } from './useRoutesNames';

export const useDeviceRoutes = (): UseDeviceRoutes => {
	const route = useRoute();

	const routeNames = useRoutesNames();

	const isDetailRoute = computed<boolean>(
		(): boolean => route.name === routeNames.deviceDetail || route.name === routeNames.connectorDetailDeviceDetail
	);

	const isSettingsRoute = computed<boolean>(
		(): boolean =>
			route.name === routeNames.deviceCreate ||
			route.name === routeNames.deviceSettings ||
			route.name === routeNames.connectorDetailDeviceCreate ||
			route.name === routeNames.connectorDetailDeviceSettings
	);

	const isDeviceRoute = computed<boolean>(
		(): boolean =>
			route.matched.find((matched) => {
				return (
					matched.name === routeNames.deviceCreate ||
					matched.name === routeNames.deviceDetail ||
					matched.name === routeNames.connectorDetailDeviceCreate ||
					matched.name === routeNames.connectorDetailDeviceDetail
				);
			}) !== undefined
	);

	return {
		isDetailRoute,
		isSettingsRoute,
		isDeviceRoute,
	};
};
