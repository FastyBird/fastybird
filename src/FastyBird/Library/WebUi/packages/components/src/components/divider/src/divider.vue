<template>
	<div
		:class="[ns.b(), ns.m(props.direction), ns.is('gradient', props.gradient)]"
		role="separator"
	>
		<div
			:class="[ns.e('inner')]"
			:style="dividerStyle"
		>
			<div
				v-if="'default' in $slots && props.direction !== 'vertical'"
				:class="[ns.e('text'), ns.is(props.contentPosition)]"
			>
				<slot />
			</div>
		</div>
	</div>
</template>

<script lang="ts" setup>
import { computed } from 'vue';

import { useNamespace } from '@fastybird/web-ui-hooks';

import { dividerProps } from './divider';

import type { CSSProperties } from 'vue';

defineOptions({
	name: 'FbDivider',
});

const props = defineProps(dividerProps);

const ns = useNamespace('divider');

const dividerStyle = computed<CSSProperties>((): CSSProperties => {
	return ns.cssVar({
		'border-style': props.borderStyle,
	}) as CSSProperties;
});
</script>
