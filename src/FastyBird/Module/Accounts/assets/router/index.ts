import { Router, RouteRecordRaw } from 'vue-router';

import { sessionGuard, anonymousGuard, authenticatedGuard, accountGuard } from '../router/guards';

import { useRoutesNames } from '../composables';

const { routeNames } = useRoutesNames();

const moduleRoutes: RouteRecordRaw[] = [
	{
		path: '/',
		name: routeNames.root,
		component: () => import('../layouts/layout-default.vue'),
		children: [
			{
				path: 'sign',
				name: 'accounts_module-sign',
				component: () => import('../layouts/layout-sign.vue'),
				children: [
					{
						path: 'in',
						name: routeNames.signIn,
						component: () => import('../views/view-sign-in.vue'),
						meta: {
							guards: ['anonymous'],
						},
					},
					{
						path: 'up',
						name: routeNames.signUp,
						component: () => import('../views/view-sign-up.vue'),
						meta: {
							guards: ['anonymous'],
						},
					},
				],
			},
			{
				path: 'reset-password',
				name: routeNames.resetPassword,
				component: () => import('../views/view-reset-password.vue'),
				meta: {
					guards: ['anonymous'],
				},
			},
			{
				path: 'account',
				name: 'accounts_module-account',
				component: () => import('../layouts/layout-account.vue'),
				children: [
					{
						path: 'profile',
						name: routeNames.accountProfile,
						component: () => import('../views/view-profile.vue'),
						meta: {
							guards: ['authenticated'],
						},
					},
					{
						path: 'password',
						name: routeNames.accountPassword,
						component: () => import('../views/view-password.vue'),
						meta: {
							guards: ['authenticated'],
						},
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

	// Register router guards
	router.beforeEach(sessionGuard);
	router.beforeEach(anonymousGuard);
	router.beforeEach(authenticatedGuard);
	router.beforeEach(accountGuard);
};
