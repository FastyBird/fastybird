<template>
	<h1 class="fb-accounts-module-sign-header__heading">
		{{ props.heading }}
	</h1>
</template>

<script setup lang="ts">
import { ILayoutSignHeaderProps } from '@/types';

const props = defineProps<ILayoutSignHeaderProps>();
</script>

<style rel="stylesheet/scss" lang="scss" scoped>
@import 'sign-header';
</style>
