import { computed, ComputedRef, onBeforeUnmount, ref, shallowRef, unref, watch } from 'vue';
import { fromPairs } from 'lodash';

import { createPopper } from '@popperjs/core';

import type { Ref } from 'vue';
import type { Instance, Modifier, Options, State, VirtualElement } from '@popperjs/core';

type ElementType = HTMLElement | undefined;
type ReferenceElement = ElementType | VirtualElement;

interface Dictionary<T> {
	[index: string]: T;
}

export type PartialOptions = Partial<Options>;

export const usePopper = (
	referenceElementRef: Ref<ReferenceElement>,
	popperElementRef: Ref<ElementType>,
	opts: Ref<PartialOptions> | PartialOptions = {} as PartialOptions
): {
	state: ComputedRef<any>;
	styles: ComputedRef<any>;
	attributes: ComputedRef<any>;
	update: () => Promise<Partial<State>> | undefined;
	forceUpdate: () => void;
	instanceRef: ComputedRef<any>;
} => {
	const stateUpdater = {
		name: 'updateState',
		enabled: true,
		phase: 'write',
		fn: ({ state }): void => {
			const derivedState = deriveState(state);

			Object.assign(states.value, derivedState);
		},
		requires: ['computeStyles'],
	} as Modifier<'updateState', any>;

	const options = computed<Options>((): Options => {
		const { onFirstUpdate, placement, strategy, modifiers } = unref(opts);

		return {
			onFirstUpdate,
			placement: placement || 'bottom',
			strategy: strategy || 'absolute',
			modifiers: [...(modifiers || []), stateUpdater, { name: 'applyStyles', enabled: false }],
		};
	});

	const instanceRef = shallowRef<Instance | undefined>();
	const states = ref<Pick<State, 'styles' | 'attributes'>>({
		styles: {
			popper: {
				position: unref(options).strategy,
				left: '0',
				top: '0',
			},
			arrow: {
				position: 'absolute',
			},
		},
		attributes: {},
	});

	const destroy = (): void => {
		if (!instanceRef.value) return;

		instanceRef.value.destroy();
		instanceRef.value = undefined;
	};

	onBeforeUnmount((): void => {
		destroy();
	});

	watch(
		(): Options => options.value,
		(newOptions: Options): void => {
			const instance = unref(instanceRef);

			if (instance) {
				instance.setOptions(newOptions);
			}
		},
		{
			deep: true,
		}
	);

	watch([referenceElementRef, popperElementRef], ([referenceElement, popperElement]): void => {
		destroy();

		if (!referenceElement || !popperElement) return;

		instanceRef.value = createPopper(referenceElement, popperElement, unref(options));
	});

	return {
		state: computed(() => ({ ...(unref(instanceRef)?.state || {}) })),
		styles: computed(() => unref(states).styles),
		attributes: computed(() => unref(states).attributes),
		update: (): Promise<Partial<State>> | undefined => unref(instanceRef)?.update(),
		forceUpdate: (): void => unref(instanceRef)?.forceUpdate(),
		// Preventing end users from modifying the instance.
		instanceRef: computed(() => unref(instanceRef)),
	};
};

const deriveState = (
	state: State
): { styles: Dictionary<Partial<CSSStyleDeclaration>>; attributes: Dictionary<{ [key: string]: string | boolean }> } => {
	const elements = Object.keys(state.elements) as any as Array<keyof State['elements']>;

	const styles: Dictionary<Partial<CSSStyleDeclaration>> = fromPairs(
		elements.map((element) => [element, state.styles[element] || {}] as [string, State['styles'][keyof State['styles']]])
	);

	const attributes: Dictionary<{ [key: string]: string | boolean }> = fromPairs(
		elements.map((element) => [element, state.attributes[element]] as [string, State['attributes'][keyof State['attributes']]])
	);

	return {
		styles,
		attributes,
	};
};

export type UsePopperReturn = ReturnType<typeof usePopper>;
