import { computed, getCurrentInstance, inject, ref, unref } from 'vue';

import { isNumber } from '@fastybird/web-ui-utils';

import type { ComputedRef, InjectionKey, Ref } from 'vue';

const zIndex = ref(0);
export const defaultInitialZIndex = 2000;

export const zIndexContextKey: InjectionKey<Ref<number | undefined>> = Symbol('zIndexContextKey');

export const useZIndex = (
	zIndexOverrides?: Ref<number>
): { initialZIndex: ComputedRef<number>; currentZIndex: ComputedRef<number>; nextZIndex: () => number } => {
	const zIndexInjection = zIndexOverrides || (getCurrentInstance() ? inject(zIndexContextKey, undefined) : undefined);

	const initialZIndex = computed(() => {
		const zIndexFromInjection = unref(zIndexInjection);

		return isNumber(zIndexFromInjection) ? zIndexFromInjection : defaultInitialZIndex;
	});

	const currentZIndex = computed(() => initialZIndex.value + zIndex.value);

	const nextZIndex = (): number => {
		zIndex.value++;

		return currentZIndex.value;
	};

	return {
		initialZIndex,
		currentZIndex,
		nextZIndex,
	};
};

export type UseZIndexReturn = ReturnType<typeof useZIndex>;
