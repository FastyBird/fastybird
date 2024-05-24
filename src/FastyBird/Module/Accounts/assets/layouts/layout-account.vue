<template>
	<el-main>
		<fb-breadcrumbs>
			<el-breadcrumb
				:id="FB_BREADCRUMBS_TARGET"
				separator="/"
			>
				<el-breadcrumb-item :to="{ path: '/' }"> {{ t('breadcrumbs.homepage') }} </el-breadcrumb-item>
				<el-breadcrumb-item :to="{ name: routeNames.account }"> {{ t('breadcrumbs.account') }} </el-breadcrumb-item>
				<el-breadcrumb-item
					v-if="route.name === routeNames.accountProfile"
					:to="{ name: routeNames.accountProfile }"
				>
					{{ t('breadcrumbs.profile') }}
				</el-breadcrumb-item>
				<el-breadcrumb-item
					v-if="route.name === routeNames.accountPassword"
					:to="{ name: routeNames.accountPassword }"
				>
					{{ t('breadcrumbs.security') }}
				</el-breadcrumb-item>
			</el-breadcrumb>
		</fb-breadcrumbs>

		<el-page-header
			v-if="!isXsBreakpoint"
			@back="onBack"
		>
			<template #content>
				<div class="flex items-center">
					<el-avatar
						:size="32"
						:src="avatarUrl"
						class="mr-3"
					/>

					<span class="text-large font-600 mr-3"> {{ t('headings.yourProfile') }} </span>
					<span class="text-sm mr-2"> {{ sessionStore.account?.email?.address || '' }} </span>
				</div>
			</template>

			<template #extra>
				<div class="flex items-center">
					<el-button
						:loading="remoteFormResult === FormResultTypes.WORKING"
						:disabled="remoteFormResult === FormResultTypes.WORKING"
						type="primary"
						@click="onSave"
					>
						{{ t('buttons.save.title') }}
					</el-button>
				</div>
			</template>
		</el-page-header>

		<fb-app-bar-heading
			v-if="isXsBreakpoint"
			teleport
		>
			<template #icon>
				<el-avatar
					:size="32"
					:src="avatarUrl"
				/>
			</template>

			<template #title>
				{{ t('headings.yourProfile') }}
			</template>

			<template #subtitle>
				{{ sessionStore.account?.email?.address || '' }}
			</template>
		</fb-app-bar-heading>

		<el-tabs
			v-if="!isXsBreakpoint"
			v-model="activeTab"
			class="mt-5"
			@tab-click="onTabClick"
		>
			<el-tab-pane
				:label="t('tabs.profile')"
				:name="PanelNames.PROFILE"
			>
				<template #label>
					<span class="flex flex-row items-center gap-2">
						<el-icon><fas-user /></el-icon>
						<span>{{ t('tabs.profile') }}</span>
					</span>
				</template>

				<RouterView
					v-if="route.name === routeNames.accountProfile"
					v-model:remote-form-submit="remoteFormSubmit"
					v-model:remote-form-result="remoteFormResult"
				/>
			</el-tab-pane>

			<el-tab-pane
				:label="t('tabs.security')"
				:name="PanelNames.SECURITY"
			>
				<template #label>
					<span class="flex flex-row items-center gap-2">
						<el-icon><fas-lock /></el-icon>
						<span>{{ t('tabs.security') }}</span>
					</span>
				</template>

				<RouterView
					v-if="route.name === routeNames.accountPassword"
					v-model:remote-form-submit="remoteFormSubmit"
					v-model:remote-form-result="remoteFormResult"
				/>
			</el-tab-pane>
		</el-tabs>

		<RouterView
			v-if="isXsBreakpoint"
			v-model:remote-form-submit="remoteFormSubmit"
			v-model:remote-form-result="remoteFormResult"
		/>
	</el-main>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue';
import { RouteRecordName, useRoute, useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import md5 from 'md5';
import { ElMain, ElPageHeader, ElButton, ElBreadcrumbItem, ElAvatar, ElIcon, ElTabs, ElTabPane, TabsPaneContext, ElBreadcrumb } from 'element-plus';

import { breakpointsBootstrapV5, useBreakpoints } from '@vueuse/core';
import { FB_BREADCRUMBS_TARGET, FbAppBarHeading, FbBreadcrumbs } from '@fastybird/web-ui-library';
import { FasUser, FasLock } from '@fastybird/web-ui-icons';

import { useRoutesNames } from '../composables';
import { useSession } from '../models';
import { FormResultTypes } from '../types';

enum PanelNames {
	PROFILE = 'profile',
	SECURITY = 'security',
}

type PanelName = PanelNames.PROFILE | PanelNames.SECURITY;

defineOptions({
	name: 'LayoutAccount',
});

const router = useRouter();
const route = useRoute();
const { t } = useI18n();

const { routeNames } = useRoutesNames();
const sessionStore = useSession();
const breakpoints = useBreakpoints(breakpointsBootstrapV5);

const isXsBreakpoint = breakpoints.smaller('sm');
const activeTab = ref<PanelName>(route.name === routeNames.accountProfile ? PanelNames.PROFILE : PanelNames.SECURITY);

const mounted = ref<boolean>(false);
const remoteFormSubmit = ref<boolean>(false);
const remoteFormResult = ref<FormResultTypes>(FormResultTypes.NONE);

const onBack = (): void => {};

const onTabClick = (pane: TabsPaneContext): void => {
	switch (pane.paneName) {
		case PanelNames.PROFILE:
			router.push({ name: routeNames.accountProfile });
			break;
		case PanelNames.SECURITY:
			router.push({ name: routeNames.accountPassword });
			break;
	}
};

const onSave = (): void => {
	remoteFormSubmit.value = true;
};

const avatarUrl = computed<string>((): string => {
	return ['//www.gravatar.com/avatar/', md5((sessionStore.account?.email?.address || '').trim().toLowerCase()), '?s=80', '&d=retro', '&r=g'].join('');
});

onMounted((): void => {
	mounted.value = true;
});

watch(
	(): RouteRecordName | string | null | undefined => route.name,
	(val: RouteRecordName | string | null | undefined): void => {
		if (mounted.value) {
			if (val === routeNames.accountProfile && activeTab.value !== PanelNames.PROFILE) {
				activeTab.value = PanelNames.PROFILE;
			} else if (val === routeNames.accountPassword && activeTab.value !== PanelNames.SECURITY) {
				activeTab.value = PanelNames.SECURITY;
			}
		}
	}
);
</script>

<i18n src="../locales/locales.json" />
