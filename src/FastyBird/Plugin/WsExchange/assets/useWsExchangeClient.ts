import { computed, inject, InjectionKey } from 'vue';

import { IWampClient, IWebSocketResult, RpCallResponse } from '@/types';

export const key: InjectionKey<IWampClient> = Symbol('wampClient');

export function useWsExchangeClient<T>(): IWebSocketResult<T> {
	const wampClient = inject(key);

	if (wampClient === undefined) {
		throw new Error('WAMP client is not installed');
	}

	return {
		status: computed<boolean>((): boolean => wampClient.isConnected.value),
		open: (): void => wampClient.open(),
		close: (): void => wampClient.close(),
		call: (...args): Promise<RpCallResponse<T>> => wampClient.call(...args),
		client: wampClient,
	};
}
