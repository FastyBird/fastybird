<template>
	<teleport
		v-if="!props.teleport || mounted"
		:disabled="!props.teleport"
		:to="`#${teleportTarget}`"
	>
		<div
			v-if="'prepend' in $slots || 'icon' in $slots || props.icon"
			:class="[ns.e('prepend')]"
		>
			<slot name="prepend">
				<el-icon
					v-if="'icon' in $slots || props.icon"
					:class="[ns.e('icon')]"
				>
					<slot name="icon">
						<component
							:is="props.icon"
							v-if="props.icon"
						/>
					</slot>
				</el-icon>
			</slot>
		</div>

		<h1 :class="[ns.e('title'), ns.em('title', `align-${props.align}`)]">
			<template v-if="'subtitle' in $slots">
				<span><slot name="title" /></span>
				<small><slot name="subtitle" /></small>
			</template>
			<template v-else>
				<slot name="title" />
			</template>
		</h1>

		<div
			v-if="'append' in $slots"
			:class="[ns.e('append')]"
		>
			<slot name="append" />
		</div>
	</teleport>
</template>

<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { ElIcon, useNamespace } from 'element-plus';

import { appBarHeadingProps } from './heading';

defineOptions({
	name: 'FbAppBarHeading',
});

const props = defineProps(appBarHeadingProps);

const ns = useNamespace('app-bar-heading');

const mounted = ref<boolean>(false);

const teleportTarget = 'fb-app-bar-heading';

onMounted((): void => {
	if (props.teleport) {
		const target: HTMLElement | null = document.getElementById(teleportTarget);

		if (target !== null) {
			target.childNodes.forEach((node) => {
				if (node instanceof HTMLElement) {
					node.style.display = 'none';
				}
			});
		}
	}

	mounted.value = true;
});

onBeforeUnmount((): void => {
	if (props.teleport) {
		const target: HTMLElement | null = document.getElementById(teleportTarget);

		if (target !== null) {
			target.childNodes.forEach((node) => {
				if (node instanceof HTMLElement && node.style.display === 'none') {
					node.style.display = '';
				}
			});
		}
	}
});
</script>
