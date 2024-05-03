import { computed, getCurrentInstance } from 'vue';
import { fromPairs } from 'lodash';

import { debugWarn } from '@fastybird/web-ui-utils';

import type { ComputedRef } from 'vue';

interface Params {
	excludeListeners?: boolean;
	excludeKeys?: ComputedRef<string[]>;
}

const DEFAULT_EXCLUDE_KEYS = ['class', 'style'];
const LISTENER_PREFIX = /^on[A-Z]/;

export const useAttrs = (params: Params = {}): ComputedRef<Record<string, any>> => {
	const { excludeListeners = false, excludeKeys } = params;
	const allExcludeKeys = computed<string[]>(() => {
		return (excludeKeys?.value || []).concat(DEFAULT_EXCLUDE_KEYS);
	});

	const instance = getCurrentInstance();

	if (!instance) {
		debugWarn('use-attrs', 'getCurrentInstance() returned null. useAttrs() must be called at the top of a setup function');
		return computed(() => ({}));
	}

	return computed(() => {
		const entries = Object.entries(instance.proxy?.$attrs || []);

		return fromPairs(entries.filter(([key]) => !allExcludeKeys.value.includes(key) && !(excludeListeners && LISTENER_PREFIX.test(key))));
	});
};
