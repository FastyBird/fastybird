import type { CSSProperties, ComputedRef, InjectionKey, Ref } from 'vue';
import type { Instance } from '@popperjs/core';

export type Measurable = {
	getBoundingClientRect: () => DOMRect;
};

export type PopperInjectionContext = {
	/** @description indicates the element that triggers popper */
	triggerRef: Ref<Measurable | undefined>;
	/** @description indicates the element of popper content */
	contentRef: Ref<HTMLElement | undefined>;
	/** @description indicates the element that popper content relative with */
	referenceRef: Ref<Measurable | undefined>;
	popperInstanceRef: Ref<Instance | undefined>;
	role: ComputedRef<string>;
};

export type PopperContentInjectionContext = {
	arrowRef: Ref<HTMLElement | undefined>;
	arrowOffset: Ref<number | undefined>;
	arrowStyle: ComputedRef<CSSProperties>;
};

export const POPPER_INJECTION_KEY: InjectionKey<PopperInjectionContext> = Symbol('popper');

export const POPPER_CONTENT_INJECTION_KEY: InjectionKey<PopperContentInjectionContext> = Symbol('popperContent');
