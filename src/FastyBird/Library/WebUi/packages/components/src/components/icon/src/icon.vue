<template>
	<i v-bind="$attrs" :class="ns.b()" :style="iconStyle">
		<slot />
	</i>
</template>

<script lang="ts" setup>
import { computed } from 'vue';

import { useNamespace } from '@fastybird/web-ui-hooks';
import { addUnit, isUndefined } from '@fastybird/web-ui-utils';

import { iconProps } from './icon';

import type { CSSProperties } from 'vue';

defineOptions({
	name: 'FbIcon',
	inheritAttrs: false,
});

const props = defineProps(iconProps);

const ns = useNamespace('icon');

const iconStyle = computed<CSSProperties>((): CSSProperties => {
	const { size, color } = props;

	if (!size && !color) {
		return {};
	}

	return {
		fontSize: isUndefined(size) ? undefined : addUnit(size),
		'--color': color,
	};
});
</script>
