import { Plugin } from 'vue';
import { Router } from 'vue-router';

export * from '@/components/types';
export * from '@/composables/types';
export * from '@/models/types';
export * from '@/types';

export type InstallFunction = Plugin & { installed?: boolean };

export interface IAccountsModuleOptions {
	router?: Router;
	meta: IAccountModuleMeta;
	configuration: IAccountModuleConfiguration;
}

export interface IAccountModuleMeta {
	author: string;
	website: string;
	version: string;
}

export interface IAccountModuleConfiguration {
	injectionKeys: {
		eventBusInjectionKey?: symbol | string;
	};
}

export type UserSignedEventType = 'in' | 'out';

export type EventBusEventsType = {
	loadingOverlay?: number | boolean;
	userSigned: UserSignedEventType;
};

export interface IRoutes {
	root: string;
	signIn: string;
	signUp: string;
	signOut: string;
	resetPassword: string;
	accountProfile: string;
	accountPassword: string;
}
