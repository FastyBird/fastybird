<template>
	<template v-if="props.teleport">
		<teleport :to="`#${teleportTarget}`">
			<div :class="[ns.b(), ns.m(`align-${props.left ? 'left' : props.right ? 'right' : 'none'}`)]">
				<slot />
			</div>
		</teleport>
	</template>

	<template v-else>
		<div :class="[ns.b(), ns.m(`align-${props.left ? 'left' : props.right ? 'right' : 'none'}`)]">
			<slot />
		</div>
	</template>
</template>

<script lang="ts" setup>
import { appBarIconProps } from './icon';

import { useNamespace } from '@fastybird/web-ui-hooks';

defineOptions({
	name: 'FbAppBarIcon',
});

const props = defineProps(appBarIconProps);

const ns = useNamespace('app-bar-icon');

const teleportTarget = `fb-app-bar-button-${props.left ? 'left' : ''}${props.right ? 'right' : ''}`;
</script>
