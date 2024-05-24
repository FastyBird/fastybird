<template>
	<div
		:role="'button' in $slots ? 'button' : undefined"
		:class="[ns.b(), ns.m('variant-' + props.variant)]"
		@click="emit('click', $event)"
	>
		<div
			v-if="'icon' in $slots"
			:class="ns.e('icon')"
		>
			<slot name="icon" />
		</div>

		<h2 :class="ns.e('title')">
			<slot name="title" />
			<small v-if="'subtitle' in $slots">
				<slot name="subtitle" />
			</small>
		</h2>

		<div
			v-if="'detail' in $slots"
			:class="ns.e('content')"
		>
			<slot name="detail" />
		</div>

		<div
			v-else-if="'button' in $slots"
			:class="ns.e('button')"
			@click.stop="void 0"
		>
			<slot name="button" />
		</div>
	</div>
</template>

<script setup lang="ts">
import { useNamespace } from 'element-plus';

import { itemEmits, itemProps } from './item';

defineOptions({
	name: 'FbListItem',
});

const props = defineProps(itemProps);
const emit = defineEmits(itemEmits);

const ns = useNamespace('list-item');
</script>
