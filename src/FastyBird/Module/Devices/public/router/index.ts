import { Router, RouteRecordRaw } from 'vue-router';

import { useRoutesNames } from '@/composables';

const { routeNames } = useRoutesNames();

const moduleRoutes: RouteRecordRaw[] = [
	{
		path: '/',
		name: routeNames.root,
		component: () => import('@/layouts/layout-default.vue'),
		children: [
			{
				path: 'devices',
				name: routeNames.devices,
				component: () => import('@/views/view-devices.vue'),
				meta: {
					guards: ['authenticated'],
				},
				children: [
					{
						path: 'connect',
						name: routeNames.deviceConnect,
						component: () => import('@/views/view-device-connect.vue'),
						props: true,
						meta: {
							guards: ['authenticated'],
						},
					},
					{
						path: ':id',
						name: routeNames.deviceDetail,
						component: () => import('@/views/view-device-detail.vue'),
						props: true,
						meta: {
							guards: ['authenticated'],
						},
						children: [
							{
								path: 'settings',
								name: routeNames.deviceSettings,
								component: () => import('@/views/view-device-settings.vue'),
								props: true,
								meta: {
									guards: ['authenticated'],
								},
							},
							{
								path: 'settings/channel/add',
								name: routeNames.deviceSettingsAddChannel,
								component: () => import('@/views/view-channel-settings.vue'),
								props: true,
								meta: {
									guards: ['authenticated'],
								},
							},
							{
								path: 'settings/channel/:channelId',
								name: routeNames.deviceSettingsEditChannel,
								component: () => import('@/views/view-channel-settings.vue'),
								props: true,
								meta: {
									guards: ['authenticated'],
								},
							},
						],
					},
				],
			},
			{
				path: 'connectors',
				name: routeNames.connectors,
				component: () => import('@/views/view-connectors.vue'),
				meta: {
					guards: ['authenticated'],
				},
				children: [
					{
						path: 'register',
						name: routeNames.connectorRegister,
						component: () => import('@/views/view-connector-register.vue'),
						props: true,
						meta: {
							guards: ['authenticated'],
						},
					},
					{
						path: ':id',
						name: routeNames.connectorDetail,
						component: () => import('@/views/view-connector-detail.vue'),
						props: true,
						meta: {
							guards: ['authenticated'],
						},
						children: [
							{
								path: 'settings',
								name: routeNames.connectorSettings,
								component: () => import('@/views/view-connector-settings.vue'),
								props: true,
								meta: {
									guards: ['authenticated'],
								},
							},
							{
								path: 'settings/device/add',
								name: routeNames.connectorSettingsAddDevice,
								component: () => import('@/views/view-device-settings.vue'),
								props: (route) => ({ id: null, connectorId: route.params.id }),
								meta: {
									guards: ['authenticated'],
								},
							},
							{
								path: 'settings/device/:deviceId',
								name: routeNames.connectorSettingsEditDevice,
								component: () => import('@/views/view-device-settings.vue'),
								props: (route) => ({ id: route.params.deviceId, connectorId: route.params.id }),
								meta: {
									guards: ['authenticated'],
								},
							},
							{
								path: 'settings/device/:deviceId/channel/add',
								name: routeNames.connectorSettingsEditDeviceAddChannel,
								component: () => import('@/views/view-channel-settings.vue'),
								props: (route) => ({ id: route.params.deviceId, connectorId: route.params.id }),
								meta: {
									guards: ['authenticated'],
								},
							},
							{
								path: 'settings/device/:deviceId/channel/:channelId',
								name: routeNames.connectorSettingsEditDeviceEditChannel,
								component: () => import('@/views/view-channel-settings.vue'),
								props: (route) => ({ id: route.params.deviceId, connectorId: route.params.id, channelId: route.params.channelId }),
								meta: {
									guards: ['authenticated'],
								},
							},
						],
					},
				],
			},
		],
	},
];

export default (router: Router): void => {
	moduleRoutes.forEach((route) => {
		router.addRoute('/', route);
	});
};
