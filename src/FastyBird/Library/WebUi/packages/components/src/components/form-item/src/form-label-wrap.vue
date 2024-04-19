<template>
	<div
		v-if="isAutoWidth"
		ref="el"
		:class="ns.be('item', 'label-wrap')"
		:style="style"
	>
		<slot />
	</div>
	<Fragment
		v-else
		ref="el"
	>
		<slot />
	</Fragment>
</template>

<script setup lang="ts">
import { Fragment, computed, inject, nextTick, onBeforeUnmount, onMounted, onUpdated, ref, watch, useSlots, getCurrentInstance } from 'vue';

import { useResizeObserver } from '@vueuse/core';
import { throwError } from '@fastybird/web-ui-utils';
import { useNamespace } from '@fastybird/web-ui-hooks';

import { formContextKey } from '../../form/src/constants';
import { formItemContextKey } from './constants';

import type { CSSProperties } from 'vue';

defineOptions({
	name: 'FbLabelWrap',
});

const props = defineProps<{ isAutoWidth: boolean; updateAll: boolean }>();
const slots = useSlots();

const instance = getCurrentInstance();

const formContext = inject(formContextKey, undefined);
const formItemContext = inject(formItemContextKey);

if (!formItemContext) throwError(instance!.type.name!, 'usage: <fb-form-item><label-wrap /></fb-form-item>');

const ns = useNamespace('form');

const el = ref<HTMLElement>();
const computedWidth = ref(0);

const getLabelWidth = (): number => {
	if (el.value?.firstElementChild) {
		const width = window.getComputedStyle(el.value.firstElementChild).width;
		return Math.ceil(Number.parseFloat(width));
	} else {
		return 0;
	}
};

const updateLabelWidth = (action: 'update' | 'remove' = 'update'): void => {
	nextTick(() => {
		if (slots.default && props.isAutoWidth) {
			if (action === 'update') {
				computedWidth.value = getLabelWidth();
			} else if (action === 'remove') {
				formContext?.deregisterLabelWidth(computedWidth.value);
			}
		}
	});
};
const updateLabelWidthFn = (): void => updateLabelWidth('update');

const autoLabelWidth = formContext?.autoLabelWidth;

const hasLabel = formItemContext?.hasLabel;

const style: CSSProperties = {};

if (hasLabel && autoLabelWidth && autoLabelWidth !== 'auto') {
	const marginWidth = Math.max(0, Number.parseInt(autoLabelWidth, 10) - computedWidth.value);
	const marginPosition = formContext.labelPosition === 'left' ? 'marginRight' : 'marginLeft';

	if (marginWidth) {
		style[marginPosition] = `${marginWidth}px`;
	}
}

onMounted((): void => {
	updateLabelWidthFn();
});

onBeforeUnmount((): void => {
	updateLabelWidth('remove');
});

onUpdated((): void => updateLabelWidthFn());

watch(computedWidth, (val: number, oldVal: number) => {
	if (props.updateAll) {
		formContext?.registerLabelWidth(val, oldVal);
	}
});

useResizeObserver(
	computed<HTMLElement | null>((): HTMLElement | null => (el.value?.firstElementChild ?? null) as HTMLElement | null),
	updateLabelWidthFn
);
</script>
