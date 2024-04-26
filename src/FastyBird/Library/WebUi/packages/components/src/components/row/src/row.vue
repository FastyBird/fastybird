<template>
	<component :is="tag" :class="rowClass" :style="rowStyle">
		<slot />
	</component>
</template>

<script setup lang="ts">
import { computed, provide } from 'vue';
import { useNamespace } from '@fastybird/web-ui-hooks';
import { rowContextKey } from './constants';
import { rowProps } from './row';

import type { CSSProperties } from 'vue';

defineOptions({
	name: 'FbRow',
});

const props = defineProps(rowProps);

const ns = useNamespace('row');
const gutter = computed(() => props.gutter);

provide(rowContextKey, {
	gutter,
});

const rowStyle = computed<CSSProperties>((): CSSProperties => {
	const styles: CSSProperties = {};
	if (!props.gutter) {
		return styles;
	}

	styles.marginRight = styles.marginLeft = `-${props.gutter / 2}px`;

	return styles;
});

const rowClass = computed<(string | string[])[]>((): (string | string[])[] => [
	ns.b(),
	ns.is(`justify-${props.justify}`, props.justify !== 'start'),
	ns.is(`align-${props.align}`, !!props.align),
]);
</script>
