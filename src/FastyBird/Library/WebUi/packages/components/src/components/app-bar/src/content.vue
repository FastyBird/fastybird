<template>
	<template v-if="props.teleport">
		<teleport :to="`#${teleportTarget}`">
			<slot />
		</teleport>
	</template>

	<template v-else>
		<slot />
	</template>
</template>

<script lang="ts" setup>
import { appBarContentProps } from './content';

defineOptions({
	name: 'FbAppBarContent',
});

const props = defineProps(appBarContentProps);

const teleportTarget = 'fb-app-bar-content';
</script>
