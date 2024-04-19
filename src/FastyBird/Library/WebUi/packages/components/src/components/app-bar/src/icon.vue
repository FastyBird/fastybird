<template>
	<template v-if="props.teleport">
		<teleport :to="`#${teleportTarget}`">
			<div
				:class="[
					'fb-theme-layout-header-icon__container',
					{ 'fb-theme-layout-header-icon__container-left': props.left },
					{ 'fb-theme-layout-header-icon__container-right': props.right },
				]"
			>
				<slot />
			</div>
		</teleport>
	</template>

	<template v-else>
		<div
			:class="[
				'fb-theme-layout-header-icon__container',
				{ 'fb-theme-layout-header-icon__container-left': props.left },
				{ 'fb-theme-layout-header-icon__container-right': props.right },
			]"
		>
			<slot />
		</div>
	</template>
</template>

<script lang="ts" setup>
import { appBarIconProps } from './icon';

defineOptions({
	name: 'FbAppBarIcon',
});

const props = defineProps(appBarIconProps);

const teleportTarget = `fb-layout-header-button-${props.left ? 'left' : ''}${props.right ? 'right' : ''}`;
</script>
