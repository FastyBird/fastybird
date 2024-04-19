import { computed, ComputedRef, onBeforeMount } from 'vue';

import { isClient } from '@fastybird/web-ui-utils';

import { useGetDerivedNamespace } from '../use-namespace';
import { useIdInjection } from '../use-id';

let cachedContainer: HTMLElement;

export const usePopperContainerId = (): {
	id: ComputedRef<string>;
	selector: ComputedRef<string>;
} => {
	const namespace = useGetDerivedNamespace();
	const idInjection = useIdInjection();

	const id = computed<string>((): string => {
		return `${namespace.value}-popper-container-${idInjection.prefix}`;
	});

	const selector = computed<string>((): string => `#${id.value}`);

	return {
		id,
		selector,
	};
};

const createContainer = (id: string): HTMLElement => {
	const container = document.createElement('div');

	container.id = id;

	document.body.appendChild(container);

	return container;
};

export const usePopperContainer = (): {
	id: ComputedRef<string>;
	selector: ComputedRef<string>;
} => {
	const { id, selector } = usePopperContainerId();

	onBeforeMount((): void => {
		if (!isClient) return;

		// This is for bypassing the error that when under testing env, we often encounter
		// document.body.innerHTML = '' situation
		// for this we need to disable the caching since it's not really needed
		if (process.env.NODE_ENV === 'test' || (!cachedContainer && !document.body.querySelector(selector.value))) {
			cachedContainer = createContainer(id.value);
		}
	});

	return {
		id,
		selector,
	};
};
