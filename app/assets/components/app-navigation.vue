<template>
	<el-scrollbar view-class="h-full">
		<el-menu
			:collapse="props.collapsed"
			:default-active="activeIndex"
			:class="[ns.b(), ns.is('collapsed', props.collapsed), ns.is('desktop', !isXsBreakpoint), ns.is('phone', isXsBreakpoint)]"
			class="h-full md:pt-5 xs:pt-4"
			router
		>
			<el-menu-item-group :class="[ns.e('group'), ns.is('desktop', !isXsBreakpoint), ns.is('phone', isXsBreakpoint)]">
				<template #title>
					{{ t('application.menu.root') }}
				</template>

				<el-menu-item
					v-for="(item, index) in mainItems"
					:key="index"
					:route="item.route"
					:index="item.index"
					@click="() => emit('click')"
				>
					<el-icon><component :is="item.icon" /></el-icon>
					<template #title>{{ item.title }}</template>
				</el-menu-item>
			</el-menu-item-group>

			<el-menu-item-group
				v-if="isXsBreakpoint"
				:class="[ns.e('group'), ns.is('phone', isXsBreakpoint)]"
				class="mt-5"
			>
				<template
					v-if="!props.collapsed"
					#title
				>
					{{ t('application.menu.user') }}
				</template>

				<el-menu-item
					v-for="(item, index) in userItems"
					:key="index"
					:route="item.route"
					:index="item.index"
					@click="() => emit('click')"
				>
					<el-icon><component :is="item.icon" /></el-icon>
					<template #title>{{ item.title }}</template>
				</el-menu-item>
			</el-menu-item-group>

			<el-menu-item-group
				v-if="isXsBreakpoint"
				:class="[ns.e('group'), ns.is('phone', isXsBreakpoint)]"
			>
				<el-menu-item
					index="3-1"
					@click="onSignOut"
				>
					<el-icon><fas-right-from-bracket /></el-icon>
					<template #title>{{ t('application.userMenu.signOut') }}</template>
				</el-menu-item>
			</el-menu-item-group>
		</el-menu>
	</el-scrollbar>
</template>

<script setup lang="ts">
import { computed, inject } from 'vue';
import { useRoute } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { ElMenu, ElMenuItem, ElMenuItemGroup, ElIcon, ElScrollbar, useNamespace } from 'element-plus';

import { breakpointsBootstrapV5, useBreakpoints } from '@vueuse/core';
import { FasGaugeHigh, FasPlug, FasEthernet, FasDiagramProject, FasUser, FasLock, FasRightFromBracket } from '@fastybird/web-ui-icons';
import { useRoutesNames as useAccountsModuleRoutesNames } from '@fastybird/accounts-module';
//import { useRoutesNames as useDevicesModuleRoutesNames } from '@fastybird/devices-module';

import { eventBusInjectionKey } from '../plugins';

interface IAppNavigationProps {
	collapsed: boolean;
}

defineOptions({
	name: 'AppNavigation',
});

const props = withDefaults(defineProps<IAppNavigationProps>(), {
	collapsed: false,
});

const emit = defineEmits<{
	(e: 'click'): void;
}>();

const route = useRoute();
const { t } = useI18n();
const ns = useNamespace('app-navigation');

const { routeNames: accountsModuleRouteNames } = useAccountsModuleRoutesNames();
//const { routeNames: devicesModuleRouteNames } = useDevicesModuleRoutesNames();

const breakpoints = useBreakpoints(breakpointsBootstrapV5);

const eventBus = inject(eventBusInjectionKey);

const isXsBreakpoint = breakpoints.smaller('sm');

const mainItems = computed(() => {
	const items = [];

	items.push({
		title: t('application.menu.dashboard'),
		icon: FasGaugeHigh,
		route: { name: 'application-home' },
		index: '1-1',
		active: route.name === 'application-home',
	});

	items.push({
		title: t('application.menu.devices'),
		icon: FasPlug,
		route: { name: 'application-home' },
		index: '1-2',
		active: route.name === 'application-home',
	});

	items.push({
		title: t('application.menu.connectors'),
		icon: FasEthernet,
		route: { name: 'application-home' },
		index: '1-3',
		active: route.name === 'application-home',
	});

	items.push({
		title: t('application.menu.triggers'),
		icon: FasDiagramProject,
		route: { name: 'application-home' },
		index: '1-4',
		active: route.name === 'application-home',
	});

	return items;
});

const userItems = computed(() => {
	const items = [];

	items.push({
		title: t('application.userMenu.accountSettings'),
		icon: FasUser,
		route: { name: accountsModuleRouteNames.accountProfile },
		index: '2-1',
		active: route.name === accountsModuleRouteNames.accountProfile,
	});

	items.push({
		title: t('application.userMenu.passwordChange'),
		icon: FasLock,
		route: { name: accountsModuleRouteNames.accountPassword },
		index: '2-2',
		active: route.name === accountsModuleRouteNames.accountPassword,
	});

	return items;
});

const activeIndex = computed<string | undefined>((): string | undefined => {
	for (const item of mainItems.value) {
		if (item.active) {
			return item.index;
		}
	}

	for (const item of userItems.value) {
		if (item.active) {
			return item.index;
		}
	}

	return;
});

const onSignOut = (): void => {
	emit('click');
	eventBus?.emit('userSigned', 'out');
};
</script>

<style rel="stylesheet/scss" lang="scss" scoped>
@import 'app-navigation.scss';
</style>
