import { Plugin } from 'vue';
import { Router } from 'vue-router';
import { Pinia } from 'pinia';

export * from '../components/types';
export * from '../composables/types';
export * from '../models/types';
export * from '../types';

export type InstallFunction = Plugin & { installed?: boolean };

export interface IAccountsModuleOptions {
	router?: Router;
	meta: IAccountsModuleMeta;
	configuration: IAccountsModuleConfiguration;
	store: Pinia;
}

export interface IAccountsModuleMeta {
	author: string;
	website: string;
	version: string;
}

export interface IAccountsModuleConfiguration {
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
	account: string;
	accountProfile: string;
	accountPassword: string;
}

export enum FormResultTypes {
	NONE = 'none',
	WORKING = 'working',
	ERROR = 'error',
	OK = 'ok',
}

export type FormResultType = FormResultTypes.NONE | FormResultTypes.WORKING | FormResultTypes.ERROR | FormResultTypes.OK;

export enum LayoutTypes {
	DEFAULT = 'default',
	PHONE = 'phone',
}

export type LayoutType = LayoutTypes.DEFAULT | LayoutTypes.PHONE;
