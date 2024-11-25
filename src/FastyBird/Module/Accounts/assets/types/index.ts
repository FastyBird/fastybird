export * from '../components/types';
export * from '../composables/types';
export * from '../models/types';
export * from './exchange';

export interface IAccountsModuleMeta {
	author: string;
	website: string;
	version: string;
	[key: string]: any;
}

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

export enum AccountState {
	ACTIVE = 'active',
	BLOCKED = 'blocked',
	DELETED = 'deleted',
	NOT_ACTIVATED = 'not_activated',
	APPROVAL_WAITING = 'approval_waiting',
}

export enum IdentityState {
	ACTIVE = 'active',
	BLOCKED = 'blocked',
	DELETED = 'deleted',
	INVALID = 'invalid',
}

export interface AccountDocument {
	id: string;
	source: string;
	first_name: string;
	last_name: string;
	middle_name: string;
	state: AccountState;
	registered: string | null;
	last_visit: string | null;
	email: string;
	language: string;
	roles: string[];
}

export interface EmailDocument {
	id: string;
	source: string;
	address: string;
	default: boolean;
	verified: boolean;
	private: boolean;
	public: boolean;
	account: string;
}

export interface IdentityDocument {
	id: string;
	source: string;
	state: IdentityState;
	uid: string;
	password?: string;
	account: string;
}

export interface RoleDocument {
	id: string;
	source: string;
	name: string;
	description: string;
	anonymous: boolean;
	authenticated: boolean;
	administrator: boolean;
	parent: string | null;
}

interface CookiesPluginCookiesConfig {
	expireTimes: string | number | Date;
	path?: string;
	domain?: string;
	secure?: boolean;
	sameSite?: string;
}

interface CookiesPluginCookies {
	config(config: CookiesPluginCookiesConfig): void;
	set(
		keyName: string,
		value: string,
		expireTimes?: string | number | Date,
		path?: string,
		domain?: string,
		secure?: boolean,
		sameSite?: string
	): this;
	get(keyName: string): string;
	remove(keyName: string, path?: string, domain?: string): boolean;
	isKey(keyName: string): boolean;
	keys(): string[];
}

export interface CookiesPlugin {
	config: (config: CookiesPluginCookiesConfig) => void;
	get: (keyName: string) => string;
	set: (
		keyName: string,
		value: string,
		expireTimes?: string | number | Date,
		path?: string,
		domain?: string,
		secure?: boolean,
		sameSite?: string
	) => CookiesPluginCookies;
	remove: (keyName: string, path?: string, domain?: string) => boolean;
	isKey: (keyName: string) => boolean;
	keys: () => string[];
}
