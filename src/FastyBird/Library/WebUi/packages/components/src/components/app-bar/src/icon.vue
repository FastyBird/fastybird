<template>
	<template v-if="props.teleport && props.align !== AppBarIconAlignTypes.NONE">
		<teleport
			v-if="mounted && props.align !== AppBarIconAlignTypes.NONE"
			:to="`#${teleportTarget}`"
		>
			<div :class="[ns.b(), ns.m(`align-${props.align}`)]">
				<slot />
			</div>
		</teleport>
	</template>
	<template v-else>
		<div :class="[ns.b(), ns.m(`align-${props.align}`)]">
			<slot />
		</div>
	</template>
</template>

<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { useNamespace } from 'element-plus';

import { AppBarIconAlignTypes, appBarIconProps } from './icon';

defineOptions({
	name: 'FbAppBarIcon',
});

const props = defineProps(appBarIconProps);

const ns = useNamespace('app-bar-icon');

const mounted = ref<boolean>(false);

const teleportTarget = `fb-app-bar-button-${props.align}`;

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
