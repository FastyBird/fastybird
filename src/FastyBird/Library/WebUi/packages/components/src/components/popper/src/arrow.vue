<template>
	<span
		ref="arrowRef"
		:class="ns.e('arrow')"
		:style="arrowStyle"
		data-popper-arrow
	/>
</template>

<script lang="ts" setup>
import { inject, onBeforeUnmount, watch } from 'vue';

import { useNamespace } from '@fastybird/web-ui-hooks';

import { POPPER_CONTENT_INJECTION_KEY } from './constants';
import { popperArrowProps } from './arrow';

defineOptions({
	name: 'FbPopperArrow',
	inheritAttrs: false,
});

const props = defineProps(popperArrowProps);

const ns = useNamespace('popper');

const { arrowOffset, arrowRef, arrowStyle } = inject(POPPER_CONTENT_INJECTION_KEY, undefined)!;

onBeforeUnmount((): void => {
	arrowRef.value = undefined;
});

watch(
	(): number => props.arrowOffset,
	(val): void => {
		arrowOffset.value = val;
	}
);

defineExpose({
	/** @description Arrow element */
	arrowRef,
});
</script>
