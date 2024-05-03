import { computed, ref, unref } from 'vue';

import { useNamespace, useZIndex } from '@fastybird/web-ui-hooks';
import { isNumber } from '@fastybird/web-ui-utils';

import type { ComputedRef, CSSProperties, Ref, StyleValue } from 'vue';
import type { UsePopperReturn } from '@fastybird/web-ui-hooks';
import type { UsePopperContentReturn } from './use-content';
import type { PopperContentProps } from '../content';

export const usePopperContentDOM = (
	props: PopperContentProps,
	{ attributes, styles, role }: Pick<UsePopperReturn, 'attributes' | 'styles'> & Pick<UsePopperContentReturn, 'role'>
): {
	ariaModal: ComputedRef<string | undefined>;
	arrowStyle: ComputedRef<CSSProperties>;
	contentAttrs: ComputedRef<{ [key: string]: string | boolean }>;
	contentClass: ComputedRef<(string | Record<string, boolean> | string[] | undefined)[]>;
	contentStyle: ComputedRef<StyleValue[]>;
	contentZIndex: Ref<number>;

	updateZIndex: () => void;
} => {
	const { nextZIndex } = useZIndex();
	const ns = useNamespace('popper');

	const contentAttrs = computed<{ [key: string]: string | boolean }>((): { [key: string]: string | boolean } => unref(attributes).popper);
	const contentZIndex = ref<number>(isNumber(props.zIndex) ? props.zIndex : nextZIndex());
	const contentClass = computed<(string | string[] | Record<string, boolean> | undefined)[]>(
		(): (string | string[] | Record<string, boolean> | undefined)[] => [ns.b(), ns.is('pure', props.pure), ns.is(props.effect), props.popperClass]
	);
	const contentStyle = computed<StyleValue[]>((): StyleValue[] => {
		return [{ zIndex: unref(contentZIndex) } as CSSProperties, unref(styles).popper as CSSProperties, props.popperStyle || {}];
	});
	const ariaModal = computed<string | undefined>((): string | undefined => (role.value === 'dialog' ? 'false' : undefined));
	const arrowStyle = computed<CSSProperties>((): CSSProperties => (unref(styles).arrow || {}) as CSSProperties);

	const updateZIndex = (): void => {
		contentZIndex.value = isNumber(props.zIndex) ? props.zIndex : nextZIndex();
	};

	return {
		ariaModal,
		arrowStyle,
		contentAttrs,
		contentClass,
		contentStyle,
		contentZIndex,

		updateZIndex,
	};
};

export type UsePopperContentDOMReturn = ReturnType<typeof usePopperContentDOM>;
