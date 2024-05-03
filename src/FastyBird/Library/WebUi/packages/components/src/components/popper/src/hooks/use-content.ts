import { computed, inject, onMounted, ref, unref, watch } from 'vue';
import { isUndefined } from 'lodash';

import { usePopper } from '@fastybird/web-ui-hooks';

import { Measurable, POPPER_INJECTION_KEY } from '../constants';
import { buildPopperOptions, unwrapMeasurableEl } from '../utils';

import type { ComputedRef, Ref, ShallowRef } from 'vue';
import type { Instance, Modifier, State } from '@popperjs/core';
import type { PartialOptions } from '@fastybird/web-ui-hooks';
import type { PopperContentProps } from '../content';

const DEFAULT_ARROW_OFFSET = 0;

export const usePopperContent = (
	props: PopperContentProps
): {
	attributes: ComputedRef<any>;
	arrowRef: Ref<HTMLElement | undefined>;
	contentRef: Ref<HTMLElement | undefined>;
	instanceRef: ShallowRef<Instance | undefined>;
	state: ComputedRef<any>;
	styles: ComputedRef<any>;
	role: ComputedRef<string>;

	forceUpdate: () => void;
	update: () => Promise<Partial<State>> | undefined;
} => {
	const { popperInstanceRef, contentRef, triggerRef, role } = inject(POPPER_INJECTION_KEY, undefined)!;

	const arrowRef = ref<HTMLElement | undefined>();
	const arrowOffset = ref<number>();

	const eventListenerModifier = computed(() => {
		return {
			name: 'eventListeners',
			enabled: !!props.visible,
		} as Modifier<'eventListeners', any>;
	});

	const arrowModifier = computed(() => {
		const arrowEl = unref(arrowRef);
		const offset = unref(arrowOffset) ?? DEFAULT_ARROW_OFFSET;
		// Seems like the `phase` and `fn` is required by Modifier type
		// But on its documentation they didn't specify that.
		// Refer to https://popper.js.org/docs/v2/modifiers/arrow/
		return {
			name: 'arrow',
			enabled: !isUndefined(arrowEl),
			options: {
				element: arrowEl,
				padding: offset,
			},
		} as any;
	});

	const options = computed<PartialOptions>((): PartialOptions => {
		return {
			onFirstUpdate: (): void => {
				update();
			},
			...buildPopperOptions(props, [unref(arrowModifier), unref(eventListenerModifier)]),
		};
	});

	const computedReference = computed<Measurable | undefined>(
		(): Measurable | undefined => unwrapMeasurableEl(props.referenceEl) || unref(triggerRef)
	);

	const { attributes, state, styles, update, forceUpdate, instanceRef } = usePopper(computedReference, contentRef, options);

	onMounted((): void => {
		watch(
			(): DOMRect | undefined => unref(computedReference)?.getBoundingClientRect(),
			(): void => {
				update();
			}
		);
	});

	watch(instanceRef, (instance) => (popperInstanceRef.value = instance));

	return {
		attributes,
		arrowRef,
		contentRef,
		instanceRef,
		state,
		styles,
		role,

		forceUpdate,
		update,
	};
};

export type UsePopperContentReturn = ReturnType<typeof usePopperContent>;
