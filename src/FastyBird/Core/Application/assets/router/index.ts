import { RouteRecordRaw, createRouter, createWebHistory } from 'vue-router';

import NProgress from 'nprogress';

import { FasGaugeHigh } from '@fastybird/web-ui-icons';

const routes: RouteRecordRaw[] = [
	{
		path: '/',
		name: 'root',
		component: () => import('../layouts/layout-default.vue'),
		meta: {
			title: 'MiniServer',
		},
		redirect: () => ({ name: 'application-home' }),
		children: [
			{
				path: '',
				name: 'application-home',
				component: () => import('../views/view-home.vue'),
				meta: {
					guards: ['authenticated'],
					title: 'Dashboard',
					icon: FasGaugeHigh,
				},
			},
		],
	},
];

const router = createRouter({
	history: createWebHistory(),
	routes,
});

router.beforeEach(() => {
	NProgress.start();
});

router.afterEach(() => {
	NProgress.done();
});

export default router;
