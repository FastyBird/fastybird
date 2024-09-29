<template>
	<metainfo>
		<template #title="{ content }">
			{{ content ? `${content} | FastyBird IO server` : `FastyBird IO server` }}
		</template>
	</metainfo>

	<el-container
		v-if="isXsBreakpoint"
		v-loading="loadingOverlay"
		direction="vertical"
		:class="[ns.b()]"
		class="h-full min-h-full max-h-full w-full min-w-full max-w-full"
	>
		<fb-app-bar
			v-if="sessionStore.isSignedIn()"
			@toggle-menu="onToggleMenu"
		>
			<template #logo>
				<router-link :to="{ name: 'root' }">
					<logo class="fill-white h-[30px]" />
				</router-link>
			</template>
		</fb-app-bar>

		<el-main class="flex-1">
			<router-view />
		</el-main>

		<el-drawer
			v-if="sessionStore.isSignedIn()"
			v-model="menuState"
			:size="'80%'"
			:class="[ns.e('mobile-menu')]"
		>
			<app-navigation
				:collapsed="menuCollapsed"
				@click="onToggleMenu"
			/>
		</el-drawer>
	</el-container>

	<el-container
		v-else
		v-loading="loadingOverlay"
		direction="horizontal"
		:class="[ns.b()]"
		class="h-full min-h-full max-h-full w-full min-w-full max-w-full"
	>
		<el-aside
			v-if="sessionStore.isSignedIn()"
			:class="[ns.e('aside'), { [ns.em('aside', 'collapsed')]: smallMenuCollapsed }]"
			class="bg-menu-background"
		>
			<app-sidebar :menu-collapsed="smallMenuCollapsed" />
		</el-aside>

		<el-container
			direction="vertical"
			class="flex-1 overflow-hidden"
		>
			<app-topbar
				v-if="sessionStore.isSignedIn()"
				v-model:menu-collapsed="smallMenuCollapsed"
			/>

			<el-main class="flex-1">
				<router-view />
			</el-main>
		</el-container>
	</el-container>
</template>

<script setup lang="ts">
import { inject, onBeforeMount, onBeforeUnmount, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useMeta } from 'vue-meta';
import { ElContainer, ElAside, ElMain, ElDrawer, vLoading, useNamespace } from 'element-plus';

import { breakpointsBootstrapV5, useBreakpoints } from '@vueuse/core';
import { FbAppBar } from '@fastybird/web-ui-library';
import { useWampV1Client } from '@fastybird/vue-wamp-v1';
import { useSession } from '@fastybird/accounts-module';

import { AppNavigation, AppSidebar, AppTopbar } from './components';
import { eventBusInjectionKey } from './plugins';

import { description } from './../package.json';

import Logo from './assets/images/fb_row.svg?component';

const router = useRouter();
const ns = useNamespace('app');
const sessionStore = useSession();
const wampV1Client = useWampV1Client();
const breakpoints = useBreakpoints(breakpointsBootstrapV5);

const eventBus = inject(eventBusInjectionKey);

const isXlBreakpoint = breakpoints.greaterOrEqual('xl');
const isXsBreakpoint = breakpoints.smaller('sm');

const loadingOverlay = ref<boolean>(false);
const menuState = ref<boolean>(false);
const menuCollapsed = ref<boolean>(isXlBreakpoint.value);
const smallMenuCollapsed = ref<boolean>(!isXlBreakpoint.value);

// Processing timer
let overlayTimer: number;

const hideOverlay = (): void => {
	loadingOverlay.value = false;

	window.clearInterval(overlayTimer);
};

const overlayLoadingListener = (status?: number | boolean): void => {
	if (typeof status === 'number') {
		overlayTimer = window.setInterval(hideOverlay, status * 1000);
		loadingOverlay.value = true;
	} else if (typeof status === 'boolean') {
		window.clearInterval(overlayTimer);

		loadingOverlay.value = status;
	} else {
		loadingOverlay.value = !loadingOverlay.value;
	}
};

const userSignStatusListener = async (state: string): Promise<void> => {
	if (state === 'in') {
		await router.push({ name: 'root' });

		wampV1Client.open();
	} else if (state === 'out') {
		wampV1Client.close();

		// Process cleanup
		sessionStore.clear();

		await router.push({ name: 'accounts_module-sign_in' });
	}
};

const onToggleMenu = (): void => {
	menuState.value = !menuState.value;
};

onBeforeMount((): void => {
	eventBus?.on('loadingOverlay', overlayLoadingListener);
	eventBus?.on('userSigned', userSignStatusListener);
});

onMounted((): void => {
	if (sessionStore.isSignedIn()) {
		wampV1Client.open();
	}
});

onBeforeUnmount((): void => {
	eventBus?.off('loadingOverlay', overlayLoadingListener);
	eventBus?.off('userSigned', userSignStatusListener);
});

useMeta({
	title: 'IoT control',
	meta: [
		{ charset: 'utf-8' },
		{
			name: 'viewport',
			content: 'width=device-width,initial-scale=1.0',
		},
		{
			hid: 'description',
			name: 'description',
			content: description ?? '',
		},
	],
	link: [
		{
			rel: 'icon',
			type: 'image/x-icon',
			href: '/favicon.ico',
		},
	],
	htmlAttrs: {
		lang: 'en',
	},
});
</script>

<style rel="stylesheet/scss" lang="scss">
@import 'App.scss';
</style>
