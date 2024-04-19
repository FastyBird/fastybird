import { tryOnScopeDispose } from '@vueuse/core';

export const useTimeout = (): {
	registerTimeout: (fn: (...args: any[]) => any, delay: number) => void;
	cancelTimeout: () => void;
} => {
	let timeoutHandle: number;

	const registerTimeout = (fn: (...args: any[]) => any, delay: number): void => {
		cancelTimeout();

		timeoutHandle = window.setTimeout(fn, delay);
	};

	const cancelTimeout = (): void => window.clearTimeout(timeoutHandle);

	tryOnScopeDispose((): void => cancelTimeout());

	return {
		registerTimeout,
		cancelTimeout,
	};
};
