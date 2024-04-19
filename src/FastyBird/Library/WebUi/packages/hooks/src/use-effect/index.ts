import { computed, inject, unref } from 'vue';

import { buildProp } from '@fastybird/web-ui-utils';
import { EFFECTS } from '@fastybird/web-ui-constants';

import type { InjectionKey, Ref } from 'vue';
import type { Effect } from '@fastybird/web-ui-constants';

export const useEffectProp = buildProp({
	type: String,
	values: EFFECTS,
	required: false,
} as const);

export const useEffectProps = {
	effect: useEffectProp,
};

export interface EffectContext {
	effect: Ref<Effect>;
}

export const EFFECT_INJECTION_KEY: InjectionKey<EffectContext> = Symbol('effect');

export const useGlobalEffect = () => {
	const injectedEffect = inject(EFFECT_INJECTION_KEY, {} as EffectContext);

	return computed<Effect>(() => {
		return unref(injectedEffect.effect) || '';
	});
};
