<template>
	<div :class="ns.b()">
		<slot />
		<transition :name="`${ns.namespace.value}-zoom-in-center`">
			<sup
				v-show="!props.hidden && (content || props.isDot)"
				:class="[ns.e('content'), ns.em('content', 'variant-' + props.variant), ns.is('fixed', 'default' in $slots), ns.is('dot', props.isDot)]"
				v-text="content"
			/>
		</transition>
	</div>
</template>

<script lang="ts" setup>
import { computed } from 'vue';

import { useNamespace } from '@fastybird/web-ui-hooks';
import { isNumber } from '@fastybird/web-ui-utils';

import { badgeProps } from './badge';

defineOptions({
	name: 'FbBadge',
});

const props = defineProps(badgeProps);

const ns = useNamespace('badge');

const content = computed<string>((): string => {
	if (props.isDot) {
		return '';
	}

	if (isNumber(props.value) && isNumber(props.max)) {
		if (props.max < props.value) {
			return `${props.max}+`;
		}

		return props.value === 0 && !props.showZero ? '' : `${props.value}`;
	}

	return `${props.value}`;
});

defineExpose({
	/** @description badge content */
	content,
});
</script>
