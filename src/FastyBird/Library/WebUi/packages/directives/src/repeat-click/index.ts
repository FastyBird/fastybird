import { isFunction } from '@fastybird/web-ui-utils';

import type { ObjectDirective } from 'vue';

export const REPEAT_INTERVAL = 100;
export const REPEAT_DELAY = 600;

export interface RepeatClickOptions {
	interval?: number;
	delay?: number;
	handler: (...args: unknown[]) => unknown;
}

const RepeatClick: ObjectDirective<HTMLElement, RepeatClickOptions | RepeatClickOptions['handler']> = {
	beforeMount(el, binding): void {
		const value = binding.value;
		const { interval = REPEAT_INTERVAL, delay = REPEAT_DELAY } = isFunction(value) ? {} : value;

		let intervalId: ReturnType<typeof setInterval> | undefined;
		let delayId: ReturnType<typeof setTimeout> | undefined;

		const handler = () => (isFunction(value) ? value() : value.handler());

		const clear = () => {
			if (delayId) {
				clearTimeout(delayId);
				delayId = undefined;
			}

			if (intervalId) {
				clearInterval(intervalId);
				intervalId = undefined;
			}
		};

		el.addEventListener('mousedown', (evt: MouseEvent): void => {
			if (evt.button !== 0) return;
			clear();
			handler();

			document.addEventListener('mouseup', (): void => clear(), {
				once: true,
			});

			delayId = setTimeout((): void => {
				intervalId = setInterval((): void => {
					handler();
				}, interval);
			}, delay);
		});
	},
};

export default RepeatClick;
