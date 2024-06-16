import { App } from 'vue';
import defaultsDeep from 'lodash.defaultsdeep';

import { registerAccountStore } from './models/account';
import { registerAccountsStore } from './models/accounts';
import { registerEmailsStore } from './models/emails';
import { registerIdentitiesStore } from './models/identities';
import { registerRolesStore } from './models/roles';
import { registerSessionStore } from './models/session';
import moduleRouter from './router';
import { IAccountsModuleOptions, InstallFunction } from './types';
import { configurationKey, metaKey } from './configuration';
import locales from './locales';

import 'virtual:uno.css';

export function createAccountsModule(): InstallFunction {
	return {
		install(app: App, options: IAccountsModuleOptions): void {
			if (this.installed) {
				return;
			}
			this.installed = true;

			if (typeof options.router === 'undefined') {
				throw new Error('Router instance is missing in module configuration');
			}

			moduleRouter(options.router);

			app.provide(metaKey, options.meta);
			app.provide(configurationKey, options.configuration);

			for (const [locale, translations] of Object.entries(locales)) {
				const currentMessages = options.i18n?.global.getLocaleMessage(locale);
				const mergedMessages = defaultsDeep(currentMessages, translations);

				options.i18n?.global.setLocaleMessage(locale, mergedMessages);
			}

			registerAccountStore(options.store);
			registerAccountsStore(options.store);
			registerEmailsStore(options.store);
			registerIdentitiesStore(options.store);
			registerRolesStore(options.store);
			registerSessionStore(options.store);
		},
	};
}

export * from './configuration';
export * from './components';
export * from './composables';
export * from './models';
export * from './router';

export * from './types';
