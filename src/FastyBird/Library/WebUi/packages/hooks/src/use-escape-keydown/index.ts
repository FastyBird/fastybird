import { onBeforeUnmount, onMounted } from 'vue';

import { isClient } from '@fastybird/web-ui-utils';
import { EVENT_CODE } from '@fastybird/web-ui-constants';

let registeredEscapeHandlers: ((e: KeyboardEvent) => void)[] = [];

const cachedHandler = (e: Event): void => {
	const event = e as KeyboardEvent;
	if (event.key === EVENT_CODE.esc) {
		registeredEscapeHandlers.forEach((registeredHandler) => registeredHandler(event));
	}
};

export const useEscapeKeydown = (handler: (e: KeyboardEvent) => void): void => {
	onMounted((): void => {
		if (registeredEscapeHandlers.length === 0) {
			document.addEventListener('keydown', cachedHandler);
		}
		if (isClient) registeredEscapeHandlers.push(handler);
	});

	onBeforeUnmount((): void => {
		registeredEscapeHandlers = registeredEscapeHandlers.filter((registeredHandler) => registeredHandler !== handler);
		if (registeredEscapeHandlers.length === 0) {
			if (isClient) document.removeEventListener('keydown', cachedHandler);
		}
	});
};
