<template>
	<teleport
		to="#fb-layout-phone-menu-items"
		:disabled="!props.teleport"
	>
		<slot />
	</teleport>
</template>

<script lang="ts" setup>
import { contentProps } from './content';

defineOptions({
	name: 'FbToolbarContent',
});

const props = defineProps(contentProps);
</script>
