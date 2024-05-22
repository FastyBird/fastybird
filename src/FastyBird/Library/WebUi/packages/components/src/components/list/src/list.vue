<template>
	<div :class="ns.b()">
		<div :class="ns.e('header')">
			<h3 :class="ns.e('title')">
				<slot name="heading" />

				<small v-if="'subheading' in $slots">
					<slot name="subheading" />
				</small>
			</h3>

			<div
				v-if="'buttons' in $slots"
				:class="ns.e('buttons')"
			>
				<slot name="buttons" />
			</div>
		</div>

		<slot />
	</div>
</template>

<script setup lang="ts">
import { useNamespace } from 'element-plus';

defineOptions({
	name: 'FbList',
});

const ns = useNamespace('list');
</script>
