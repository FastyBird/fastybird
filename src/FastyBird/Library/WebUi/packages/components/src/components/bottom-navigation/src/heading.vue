<template>
	<teleport
		v-if="mounted"
		:to="`#${teleportTarget}`"
		:disabled="!props.teleport"
	>
		<slot />
	</teleport>
</template>

<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from 'vue';

import { headingProps } from './heading';

defineOptions({
	name: 'FbToolbarHeading',
});

const props = defineProps(headingProps);

const mounted = ref<boolean>(false);
const targetInnerText = ref<string>('');
const targetInnerHTML = ref<string>('');

const teleportTarget = 'fb-bottom-menu-heading';

onMounted((): void => {
	if (props.teleport) {
		const target: HTMLElement | null = document.getElementById(teleportTarget);

		if (target !== null) {
			targetInnerText.value = target.innerText;
			targetInnerHTML.value = target.innerHTML;

			target.innerText = '';
			target.innerHTML = '';
		}
	}

	mounted.value = true;
});

onBeforeUnmount((): void => {
	if (props.teleport) {
		const target: HTMLElement | null = document.getElementById(teleportTarget);

		if (target !== null) {
			target.innerText = targetInnerText.value;
			target.innerHTML = targetInnerHTML.value;
		}
	}
});
</script>
