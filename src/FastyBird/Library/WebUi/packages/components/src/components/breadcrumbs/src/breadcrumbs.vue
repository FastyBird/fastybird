<template>
	<teleport
		v-if="mounted"
		:to="`#${props.target}`"
	>
		<slot />
	</teleport>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue';

import { breadcrumbsProps } from './breadcrumbs';

defineOptions({
	name: 'FbBreadcrumbs',
});

const props = defineProps(breadcrumbsProps);

const mounted = ref<boolean>(false);

onMounted((): void => {
	mounted.value = true;
});
</script>
