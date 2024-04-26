import { unrefElement } from '@vueuse/core';
import { isClient } from '@fastybird/web-ui-utils';

import type { ComponentPublicInstance } from 'vue';
import type { MaybeRef } from '@vueuse/core';
import type { Modifier } from '@popperjs/core';
import type { Measurable } from './constants';
import type { PopperCoreConfigProps } from './content';

export const buildPopperOptions = (props: PopperCoreConfigProps, modifiers: Modifier<any, any>[] = []): PopperCoreConfigProps => {
	const { placement, strategy, popperOptions } = props;
	const options = {
		placement,
		strategy,
		...popperOptions,
		modifiers: [...genModifiers(props), ...modifiers],
	};

	deriveExtraModifiers(options, popperOptions?.modifiers);

	return options;
};

export const unwrapMeasurableEl = ($el: MaybeRef<Measurable | undefined | ComponentPublicInstance>): HTMLElement | undefined => {
	if (!isClient) {
		return;
	}

	return unrefElement($el as HTMLElement);
};

const genModifiers = (options: PopperCoreConfigProps): any[] => {
	const { offset, gpuAcceleration, fallbackPlacements } = options;

	return [
		{
			name: 'offset',
			options: {
				offset: [0, offset ?? 12],
			},
		},
		{
			name: 'preventOverflow',
			options: {
				padding: {
					top: 2,
					bottom: 2,
					left: 5,
					right: 5,
				},
			},
		},
		{
			name: 'flip',
			options: {
				padding: 5,
				fallbackPlacements,
			},
		},
		{
			name: 'computeStyles',
			options: {
				gpuAcceleration,
			},
		},
	];
};

const deriveExtraModifiers = (options: any, modifiers: PopperCoreConfigProps['popperOptions']['modifiers']): void => {
	if (modifiers) {
		options.modifiers = [...options.modifiers, ...(modifiers ?? [])];
	}
};
