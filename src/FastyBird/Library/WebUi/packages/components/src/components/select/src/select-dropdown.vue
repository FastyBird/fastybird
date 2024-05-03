<template>
	<div
		:class="[ns.b('dropdown'), ns.is('multiple', props.multiple), props.popperClass]"
		:style="{ [props.fitInputWidth ? 'width' : 'minWidth']: minWidth }"
	>
		<div v-if="'header' in $slots" :class="ns.be('dropdown', 'header')">
			<slot name="header" />
		</div>

		<slot />

		<div v-if="'footer' in $slots" :class="ns.be('dropdown', 'footer')">
			<slot name="footer" />
		</div>
	</div>
</template>

<script lang="ts" setup>
import { inject, onMounted, ref } from 'vue';

import { useResizeObserver } from '@vueuse/core';
import { useNamespace } from '@fastybird/web-ui-hooks';
import { selectDropdownProps } from './select-dropdown';

import { selectContextKey } from './constants';

defineOptions({
	name: 'FbSelectDropdown',
});

const props = defineProps(selectDropdownProps);

const ns = useNamespace('select');

const select = inject(selectContextKey)!;

const minWidth = ref<string>('');

const updateMinWidth = (): void => {
	minWidth.value = `${select.selectRef?.offsetWidth}px`;
};

onMounted((): void => {
	// TODO: updatePopper
	// popper.value.update()
	updateMinWidth();
	useResizeObserver(select.selectRef, updateMinWidth);
});
</script>
