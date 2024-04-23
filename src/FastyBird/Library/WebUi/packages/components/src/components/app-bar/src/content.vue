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
import { useNamespace } from '@fastybird/web-ui-hooks';

import { appBarContentProps } from './content';

defineOptions({
	name: 'FbAppBarContent',
});

const props = defineProps(appBarContentProps);

const ns = useNamespace('app-bar-content');

const teleportTarget = 'fb-app-bar-content';
</script>
