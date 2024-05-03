<template>
	<div :class="ns.b()">
		<div v-if="'left' in $slots" :class="ns.e('left')">
			<slot name="left" />
		</div>

		<div :class="ns.e('body')">
			<h3>
				<slot name="heading" />
			</h3>

			<p v-if="'description' in $slots">
				<slot name="description" />
			</p>

			<p v-if="'action' in $slots">
				<slot name="action" />
			</p>
		</div>

		<div v-if="'right' in $slots" :class="ns.e('right')">
			<slot name="right" />
		</div>
	</div>
</template>

<script lang="ts" setup>
import { useNamespace } from '@fastybird/web-ui-hooks';

defineOptions({
	name: 'FbMediaItem',
});

const ns = useNamespace('media-item');
</script>
