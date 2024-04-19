import { unref } from 'vue';
import { buildProps, definePropType, isNumber } from '@fastybird/web-ui-utils';

import { useTimeout } from '../use-timeout';

import type { ExtractPropTypes, ToRefs } from 'vue';

export const useDelayedToggleProps = buildProps({
	/**
	 * @description delay of appearance, in millisecond
	 */
	showAfter: {
		type: definePropType<number>(Number),
		default: 0,
	},
	/**
	 * @description delay of disappear, in millisecond
	 */
	hideAfter: {
		type: definePropType<number>(Number),
		default: 200,
	},
	/**
	 * @description disappear automatically, in millisecond
	 */
	autoClose: {
		type: definePropType<number>(Number),
		default: 0,
	},
} as const);

export type UseDelayedToggleProps = {
	open: (event?: Event) => void;
	close: (event?: Event) => void;
} & ToRefs<ExtractPropTypes<typeof useDelayedToggleProps>>;

export const useDelayedToggle = ({
	showAfter,
	hideAfter,
	autoClose,
	open,
	close,
}: UseDelayedToggleProps): {
	onOpen: (event?: Event) => void;
	onClose: (event?: Event) => void;
} => {
	const { registerTimeout } = useTimeout();
	const { registerTimeout: registerTimeoutForAutoClose, cancelTimeout: cancelTimeoutForAutoClose } = useTimeout();

	const onOpen = (event?: Event): void => {
		registerTimeout((): void => {
			open(event);

			const _autoClose = unref(autoClose);

			if (isNumber(_autoClose) && _autoClose > 0) {
				registerTimeoutForAutoClose((): void => {
					close(event);
				}, _autoClose);
			}
		}, unref(showAfter));
	};

	const onClose = (event?: Event): void => {
		cancelTimeoutForAutoClose();

		registerTimeout((): void => {
			close(event);
		}, unref(hideAfter));
	};

	return {
		onOpen,
		onClose,
	};
};
