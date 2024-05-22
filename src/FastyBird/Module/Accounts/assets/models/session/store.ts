import { defineStore, Pinia, Store } from 'pinia';
import axios from 'axios';
import { useCookies } from 'vue3-cookies';
import { jwtDecode } from 'jwt-decode';
import { Jsona } from 'jsona';
import get from 'lodash/get';

import { ModulePrefix } from '@fastybird/metadata-library';

import { ApiError } from '../../errors';
import { JsonApiJsonPropertiesMapper, JsonApiModelPropertiesMapper } from '../../jsonapi';
import { useAccounts } from '../../models';
import { IAccount } from '../accounts/types';

import {
	ISessionCreateActionPayload,
	ISession,
	ISessionResponseJson,
	ISessionState,
	ISessionResponseModel,
	ISessionRecordFactoryPayload,
} from './types';

export const ACCESS_TOKEN_COOKIE_NAME = 'token';
export const REFRESH_TOKEN_COOKIE_NAME = 'refresh_token';

const jsonApiFormatter = new Jsona({
	modelPropertiesMapper: new JsonApiModelPropertiesMapper(),
	jsonPropertiesMapper: new JsonApiJsonPropertiesMapper(),
});

const readCookie = (name: string): string | null => {
	const { cookies } = useCookies();

	if (cookies.get(name) !== null && typeof cookies.get(name) !== 'undefined' && cookies.get(name) !== '') {
		return cookies.get(name);
	}

	return null;
};

const recordFactory = (data: ISessionRecordFactoryPayload): ISession => {
	const { cookies } = useCookies();

	const decodedAccessToken = jwtDecode<{ exp: number; user: string }>(data.accessToken);
	const decodedRefreshToken = jwtDecode<{ exp: number; user: string }>(data.refreshToken);

	const state = {
		accessToken: data.accessToken,
		refreshToken: data.refreshToken,
		tokenExpiration: new Date(decodedAccessToken.exp * 1000).toISOString(),
		tokenType: data.tokenType,

		accountId: get(decodedAccessToken, 'user'),
	};

	cookies.set(
		ACCESS_TOKEN_COOKIE_NAME,
		data.accessToken,
		new Date(decodedAccessToken.exp * 1000).getTime() / 1000 - new Date().getTime() / 1000,
		'/'
	);

	cookies.set(
		REFRESH_TOKEN_COOKIE_NAME,
		data.refreshToken,
		new Date(decodedRefreshToken.exp * 1000).getTime() / 1000 - new Date().getTime() / 1000,
		'/'
	);

	return state;
};

export const useSession = defineStore('accounts_module_session', {
	state: (): ISessionState => {
		return {
			semaphore: {
				fetching: false,
				creating: false,
				updating: false,
			},

			data: {
				accessToken: null,
				refreshToken: null,
				tokenType: 'Bearer',
				tokenExpiration: null,
				accountId: null,
			},
		};
	},

	getters: {
		accessToken: (state): string | null => {
			return state.data.accessToken;
		},

		refreshToken: (state): string | null => {
			return state.data.refreshToken;
		},

		accountId: (state): string | null => {
			return state.data.accountId;
		},

		account: (state): IAccount | null => {
			if (state.data.accountId === null) {
				return null;
			}

			const accountsStore = useAccounts();

			return accountsStore.findById(state.data.accountId);
		},

		isSignedIn: (state): boolean => {
			return state.data.accountId !== null;
		},
	},

	actions: {
		initialize(): void {
			if (this.data.accessToken !== null) return;

			const accessTokenCookie = readCookie(ACCESS_TOKEN_COOKIE_NAME);
			const refreshTokenCookie = readCookie(REFRESH_TOKEN_COOKIE_NAME);

			this.data.accessToken = null;
			this.data.refreshToken = null;
			this.data.tokenExpiration = null;
			this.data.accountId = null;

			if (refreshTokenCookie !== null) {
				this.data.refreshToken = refreshTokenCookie;
			} else {
				return;
			}

			if (accessTokenCookie !== null) {
				const decodedAccessToken = jwtDecode<{ exp: number; user: string }>(accessTokenCookie);

				this.data.accessToken = accessTokenCookie;
				this.data.tokenExpiration = new Date(decodedAccessToken.exp * 1000).toISOString();
				this.data.accountId = decodedAccessToken.user;
			}
		},

		clear(): void {
			const { cookies } = useCookies();

			this.$reset();

			cookies.remove(ACCESS_TOKEN_COOKIE_NAME);
			cookies.remove(REFRESH_TOKEN_COOKIE_NAME);
		},

		async fetch(): Promise<boolean> {
			if (this.semaphore.fetching) {
				return false;
			}

			const accountsStore = useAccounts();

			this.semaphore.fetching = true;

			try {
				const response = await axios.get<ISessionResponseJson>(`/${ModulePrefix.MODULE_ACCOUNTS}/v1/session`);

				const responseModel = jsonApiFormatter.deserialize(response.data) as ISessionResponseModel;

				this.data = recordFactory({
					accessToken: responseModel.token,
					refreshToken: responseModel.refresh,
					tokenType: responseModel.tokenType,
				});

				await accountsStore.get({ id: responseModel.account.id });

				return true;
			} catch (e: any) {
				throw new ApiError('session.fetch.failed', e, 'Fetching session failed.');
			} finally {
				this.semaphore.fetching = false;
			}
		},

		async create(payload: ISessionCreateActionPayload): Promise<boolean> {
			if (this.semaphore.creating) {
				return false;
			}

			const accountsStore = useAccounts();

			const dataFormatter = new Jsona();

			this.semaphore.creating = true;

			try {
				const response = await axios.post<ISessionResponseJson>(
					`/${ModulePrefix.MODULE_ACCOUNTS}/v1/session`,
					dataFormatter.serialize({
						stuff: {
							type: 'com.fastybird.accounts-module/session',

							uid: payload.uid,
							password: payload.password,
						},
					})
				);

				const responseModel = jsonApiFormatter.deserialize(response.data) as ISessionResponseModel;

				this.data = recordFactory({
					accessToken: responseModel.token,
					refreshToken: responseModel.refresh,
					tokenType: responseModel.tokenType,
				});

				await accountsStore.get({ id: responseModel.account.id });

				return true;
			} catch (e: any) {
				throw new ApiError('session.create.failed', e, 'Create session failed.');
			} finally {
				this.semaphore.creating = false;
			}
		},

		async refresh(): Promise<boolean> {
			if (this.semaphore.updating) {
				return false;
			}

			const { cookies } = useCookies();

			cookies.remove(ACCESS_TOKEN_COOKIE_NAME);

			const refreshToken = readCookie(REFRESH_TOKEN_COOKIE_NAME);

			if (refreshToken === null) {
				return false;
			}

			const dataFormatter = new Jsona();

			this.semaphore.updating = true;

			try {
				const response = await axios.patch<ISessionResponseJson>(
					`/${ModulePrefix.MODULE_ACCOUNTS}/v1/session`,
					dataFormatter.serialize({
						stuff: {
							type: 'com.fastybird.accounts-module/session',

							refresh: refreshToken,
						},
					})
				);

				const responseModel = jsonApiFormatter.deserialize(response.data) as ISessionResponseModel;

				this.data = recordFactory({
					accessToken: responseModel.token,
					refreshToken: responseModel.refresh,
					tokenType: responseModel.tokenType,
				});

				return true;
			} catch (e: any) {
				throw new ApiError('session.refresh.failed', e, 'Refresh session failed.');
			} finally {
				this.semaphore.updating = false;
			}
		},
	},
});

export const registerSessionStore = (pinia: Pinia): Store => {
	return useSession(pinia);
};
