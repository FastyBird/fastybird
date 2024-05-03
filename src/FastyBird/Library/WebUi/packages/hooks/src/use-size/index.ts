import { computed, inject, unref } from 'vue';

import { buildProp } from '@fastybird/web-ui-utils';
import { COMPONENT_SIZES } from '@fastybird/web-ui-constants';

import type { ComputedRef, InjectionKey, Ref } from 'vue';
import type { ComponentSize } from '@fastybird/web-ui-constants';

export const useSizeProp = buildProp({
	type: String,
	values: COMPONENT_SIZES,
	required: false,
});

export const useSizeProps = {
	size: useSizeProp,
};

export interface SizeContext {
	size: Ref<ComponentSize>;
}

export const SIZE_INJECTION_KEY: InjectionKey<SizeContext> = Symbol('size');

export const useGlobalSize = (): ComputedRef<ComponentSize> => {
	const injectedSize = inject(SIZE_INJECTION_KEY, {} as SizeContext);

	return computed<ComponentSize>(() => {
		return unref(injectedSize.size) || '';
	});
};
