import { computed } from 'vue';
import { useRoute } from 'vue-router';

import { useRoutesNames } from '../composables';

import { UseChannelRoutes } from './types';

export const useChannelRoutes = (): UseChannelRoutes => {
	const route = useRoute();

	const routeNames = useRoutesNames();

	const isDetailRoute = computed<boolean>(
		(): boolean => route.name === routeNames.channelDetail || route.name === routeNames.connectorDetailDeviceDetailChannelDetail
	);

	const isSettingsRoute = computed<boolean>(
		(): boolean =>
			route.name === routeNames.channelCreate ||
			route.name === routeNames.channelSettings ||
			route.name === routeNames.connectorDetailDeviceDetailChannelCreate ||
			route.name === routeNames.connectorDetailDeviceDetailChannelSettings
	);

	const isChannelRoute = computed<boolean>(
		(): boolean =>
			route.matched.find((matched) => {
				return (
					matched.name === routeNames.channelCreate ||
					matched.name === routeNames.channelDetail ||
					matched.name === routeNames.connectorDetailDeviceDetailChannelCreate ||
					matched.name === routeNames.connectorDetailDeviceDetailChannelDetail
				);
			}) !== undefined
	);

	return {
		isDetailRoute,
		isSettingsRoute,
		isChannelRoute,
	};
};
