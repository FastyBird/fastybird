<template>
	<span v-bind="$attrs" :class="[ns.b(), ns.m('variant-' + props.variant ?? VariantTypes.DEFAULT)]">
		<fb-icon :class="ns.e('icon')" :size="props.size">
			<slot name="icon" />
		</fb-icon>

		<fb-icon :class="ns.e('child-icon')" :size="childSize">
			<slot name="child" />
		</fb-icon>
	</span>
</template>

<script setup lang="ts">
import { computed } from 'vue';

import { VariantTypes } from '@fastybird/web-ui-constants';
import { useNamespace } from '@fastybird/web-ui-hooks';

import FbIcon from '../../icon/src/icon.vue';
import { iconWithChildProps } from './icon-with-child';

defineOptions({
	name: 'FbIconWithChild',
});

const props = defineProps(iconWithChildProps);

const ns = useNamespace('icon-with-child');

const childSize = computed<number>((): number => {
	if (Number.isInteger(props.size)) {
		const size = (props.size as number) * 0.4;

		return size > 20 ? 20 : size;
	}

	return 20;
});
</script>
