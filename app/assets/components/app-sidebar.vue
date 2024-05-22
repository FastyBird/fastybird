<template>
	<div
		:class="ns.b()"
		class="flex flex-col h-full"
	>
		<div
			:class="ns.e('logo')"
			class="p-1 dark:bg-menu-background bg-brand-primary h-[50px] flex flex-row justify-center"
		>
			<router-link
				:to="{ name: 'root' }"
				class="flex flex-row justify-center items-center overflow-hidden"
			>
				<logo-bird class="fill-white dark:fill-brand-primary w-[55px]" />

				<logo-fasty-bird
					v-if="!props.menuCollapsed"
					class="fill-white dark:fill-brand-primary w-[125px] ml-2 mt-2"
				/>
			</router-link>
		</div>

		<app-navigation :collapsed="props.menuCollapsed" />
	</div>
</template>

<script setup lang="ts">
import { useNamespace } from 'element-plus';

import LogoBird from '../assets/images/fb_bird.svg?component';
import LogoFastyBird from '../assets/images/fb_fastybird.svg?component';

import AppNavigation from './app-navigation.vue';

interface IAppSidebarProps {
	menuCollapsed: boolean;
}

defineOptions({
	name: 'AppSidebar',
});

const props = withDefaults(defineProps<IAppSidebarProps>(), {
	menuCollapsed: false,
});

const ns = useNamespace('app-sidebar');
</script>

<style rel="stylesheet/scss" lang="scss" scoped>
@import 'app-sidebar.scss';
</style>
