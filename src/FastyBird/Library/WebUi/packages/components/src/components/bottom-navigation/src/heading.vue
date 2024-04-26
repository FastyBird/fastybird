<template>
	<teleport to="#fb-layout-phone-menu-heading" :disabled="!props.teleport">
		<slot />
	</teleport>
</template>

<script lang="ts" setup>
import { headingProps } from './heading';

defineOptions({
	name: 'FbToolbarHeading',
});

const props = defineProps(headingProps);
</script>
