import { isFunction } from '../types';

// @ts-ignore
import type { ComponentPublicInstance, Ref } from 'vue';

export type RefSetter = (el: Element | ComponentPublicInstance | undefined) => void;

export const composeRefs = (...refs: (Ref<HTMLElement | undefined> | RefSetter)[]): ((el: Element | ComponentPublicInstance | null) => void) => {
	return (el: Element | ComponentPublicInstance | null): void => {
		refs.forEach((ref) => {
			if (isFunction(ref)) {
				ref(el as Element | ComponentPublicInstance);
			} else {
				ref.value = el as HTMLElement | undefined;
			}
		});
	};
};
