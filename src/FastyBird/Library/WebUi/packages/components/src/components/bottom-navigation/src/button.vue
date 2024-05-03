<template>
	<teleport to="#fb-layout-phone-menu-button" :disabled="!props.teleport">
		<slot />
	</teleport>
</template>

<script lang="ts" setup>
import { buttonProps } from './button';

defineOptions({
	name: 'FbToolbarButton',
});

const props = defineProps(buttonProps);
</script>
