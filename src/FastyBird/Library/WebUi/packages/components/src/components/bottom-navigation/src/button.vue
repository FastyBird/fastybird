<template>
	<template v-if="props.teleport">
		<teleport
			v-if="mounted"
			to="#fb-bottom-menu-button"
		>
			<slot />
		</teleport>
	</template>
	<template v-else>
		<slot />
	</template>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue';

import { buttonProps } from './button';

defineOptions({
	name: 'FbToolbarButton',
});

const props = defineProps(buttonProps);

const mounted = ref<boolean>(false);

onMounted((): void => {
	mounted.value = true;
});
</script>
