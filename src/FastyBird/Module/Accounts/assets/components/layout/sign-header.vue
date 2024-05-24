<template>
	<h1 class="text-center capitalize mx-0 my-8 font-300 font-size-[1.8rem]">
		{{ props.heading }}
	</h1>
</template>

<script setup lang="ts">
import { ILayoutSignHeaderProps } from './sign-header.types';

defineOptions({
	name: 'SignHeader',
});

const props = defineProps<ILayoutSignHeaderProps>();
</script>
