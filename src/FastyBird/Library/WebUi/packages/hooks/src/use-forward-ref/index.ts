import { provide } from 'vue';

import type { InjectionKey, ObjectDirective, Ref } from 'vue';

type ForwardRefSetter = <T>(el: T) => void;

export type ForwardRefInjectionContext = {
	setForwardRef: ForwardRefSetter;
};

export const FORWARD_REF_INJECTION_KEY: InjectionKey<ForwardRefInjectionContext> = Symbol('fbForwardRef');

export const useForwardRef = <T>(forwardRef: Ref<T>): void => {
	const setForwardRef = (el: T): void => {
		forwardRef.value = el;
	};

	provide(FORWARD_REF_INJECTION_KEY, {
		setForwardRef: setForwardRef as ForwardRefSetter,
	});
};

export const useForwardRefDirective = (setForwardRef: ForwardRefSetter): ObjectDirective => {
	return {
		mounted(el): void {
			setForwardRef(el);
		},
		updated(el): void {
			setForwardRef(el);
		},
		unmounted(): void {
			setForwardRef(null);
		},
	};
};
