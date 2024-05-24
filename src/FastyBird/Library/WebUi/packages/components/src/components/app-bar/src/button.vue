<template>
	<teleport
		v-if="(!props.teleport || mounted) && props.align !== AppBarButtonAlignTypes.NONE"
		:disabled="!props.teleport"
		:to="`#${teleportTarget}`"
	>
		<el-button
			v-bind="props"
			:size="props.small ? 'small' : 'large'"
			:circle="'icon' in $slots"
			:class="[ns.b(), ns.m(`align-${props.align}`)]"
			type="primary"
			@click="emit('click', $event)"
		>
			<template v-if="'icon' in $slots">
				<slot name="icon" />
			</template>

			<slot />
		</el-button>
	</teleport>
</template>

<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { ElButton, useNamespace } from 'element-plus';

import { AppBarButtonAlignTypes, appBarButtonEmits, appBarButtonProps } from './button';

defineOptions({
	name: 'FbAppBarButton',
});

const props = defineProps(appBarButtonProps);
const emit = defineEmits(appBarButtonEmits);

const ns = useNamespace('app-bar-button');

const mounted = ref<boolean>(false);

let teleportTarget = 'fb-app-bar-button';

if (props.small) {
	teleportTarget = `${teleportTarget}-small`;
} else {
	teleportTarget = `${teleportTarget}-${props.align}`;
}

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
