<template>
	<template v-if="props.teleport">
		<teleport
			v-if="mounted"
			:to="`#${teleportTarget}`"
		>
			<slot />
		</teleport>
	</template>
	<template v-else>
		<slot />
	</template>
</template>

<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from 'vue';

import { contentProps } from './content';

defineOptions({
	name: 'FbToolbarContent',
});

const props = defineProps(contentProps);

const mounted = ref<boolean>(false);
const targetInnerText = ref<string>('');
const targetInnerHTML = ref<string>('');

const teleportTarget = 'fb-bottom-menu-items';

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
