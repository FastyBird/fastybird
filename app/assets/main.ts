import { createApp } from 'vue';
import { createPinia } from 'pinia';
import { createMetaManager, plugin as metaPlugin } from 'vue-meta';
import get from 'lodash.get';
import { createWampV1Client } from '@fastybird/vue-wamp-v1';

import { version } from './../package.json';

import App from './App.vue';
import i18n from './locales';
import router from './router';

import { backendPlugin, eventBusPlugin, eventBusInjectionKey } from './plugins';

import { createAccountsModule, IAccountsModuleOptions } from '@fastybird/accounts-module';
import { createDevicesModule, IDevicesModuleOptions } from '@fastybird/devices-module';

import 'nprogress/nprogress.css';
import '@fastybird/web-ui-theme-chalk/src/index.scss';
import 'virtual:uno.css';
import './styles/base.scss';

const pinia = createPinia();
const app = createApp(App);

app.use(i18n);
app.use(createMetaManager());
app.use(metaPlugin);
app.use(pinia);
app.use(createWampV1Client(), {
	host: 'ws://localhost:3000/ws-exchange',
	debug: true,
});

// Register app plugins
app.use(backendPlugin, {
	apiPrefix: get(import.meta.env, 'FB_APP_PARAMETER__API_PREFIX', '/api'),
	apiTarget: get(import.meta.env, 'FB_APP_PARAMETER__API_TARGET', null),
	apiKey: get(import.meta.env, 'FB_APP_PARAMETER__API_KEY', null),
	apiPrefixedModules: `${get(import.meta.env, 'FB_APP_PARAMETER__API_PREFIXED_MODULES', true)}`.toLowerCase() === 'true',
});

app.use(eventBusPlugin, {});

// Register app modules
app.use(createAccountsModule(), {
	router,
	meta: {
		author: 'FastyBird s.r.o.',
		website: 'https://www.fastybird.com',
		version: version,
	},
	configuration: {
		injectionKeys: {
			eventBusInjectionKey,
		},
	},
	store: pinia,
	i18n,
} as IAccountsModuleOptions);

app.use(createDevicesModule(), {
	router,
	meta: {
		author: 'FastyBird s.r.o.',
		website: 'https://www.fastybird.com',
		version: version,
	},
	configuration: {},
	store: pinia,
	i18n,
} as IDevicesModuleOptions);

app.use(router);

router.isReady().then(() => app.mount('#app'));
