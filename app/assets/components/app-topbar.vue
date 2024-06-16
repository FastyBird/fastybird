<template>
	<el-header
		:class="ns.b()"
		class="flex flex-row items-center justify-between px-2 h-[56px]"
	>
		<div class="flex flex-row items-center gap-5">
			<el-button
				type="primary"
				circle
				link
				@click="onToggleMenu"
			>
				<template #icon>
					<fas-bars />
				</template>
			</el-button>

			<div :id="FB_BREADCRUMBS_TARGET" />
		</div>

		<div class="flex flex-row items-center gap-5">
			<div @click.stop="onSwitchTheme">
				<el-switch
					v-model="darkMode"
					:before-change="beforeThemeChange"
				>
					<template #active-action>
						<fas-moon class="dark-icon h-[14px]" />
					</template>
					<template #inactive-action>
						<fas-sun class="light-icon h-[14px]" />
					</template>
				</el-switch>
			</div>

			<el-dropdown trigger="click">
				<div class="flex items-center cursor-pointer">
					<app-gravatar
						v-if="sessionStore.account?.email"
						:email="sessionStore.account.email.address"
						class="w-[32px] rounded-[50%]"
					/>
					<span class="text-14px pl-[5px]">{{ sessionStore.account?.name }}</span>
				</div>

				<template #dropdown>
					<el-dropdown-menu>
						<el-dropdown-item @click="() => router.push({ name: accountsModuleRouteNames.accountProfile })">
							{{ t('application.userMenu.accountSettings') }}
						</el-dropdown-item>
						<el-dropdown-item @click="() => router.push({ name: accountsModuleRouteNames.accountPassword })">
							{{ t('application.userMenu.passwordChange') }}
						</el-dropdown-item>
						<el-dropdown-item divided>
							<div @click="onLock">{{ t('application.userMenu.lockScreen') }}</div>
						</el-dropdown-item>
						<el-dropdown-item>
							<div @click="onSignOut">{{ t('application.userMenu.signOut') }}</div>
						</el-dropdown-item>
					</el-dropdown-menu>
				</template>
			</el-dropdown>
		</div>
	</el-header>
</template>

<script setup lang="ts">
import { inject, nextTick, ref, watch } from 'vue';
import { useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { useNamespace, ElButton, ElHeader, ElDropdown, ElDropdownMenu, ElDropdownItem, ElSwitch } from 'element-plus';

import { FasBars, FasSun, FasMoon } from '@fastybird/web-ui-icons';
import { FB_BREADCRUMBS_TARGET } from '@fastybird/web-ui-library';
import { useRoutesNames as useAccountsModuleRoutesNames, useSession } from '@fastybird/accounts-module';

import { eventBusInjectionKey } from '../plugins';
import { isDark, toggleDark } from '../composables';
import AppGravatar from './app-gravatar.vue';

interface IAppTopbarProps {
	menuCollapsed: boolean;
}

defineOptions({
	name: 'AppTopbar',
});

const props = withDefaults(defineProps<IAppTopbarProps>(), {
	menuCollapsed: false,
});

const emit = defineEmits<{
	(e: 'update:menuCollapsed', menuCollapsed: boolean): void;
}>();

const router = useRouter();
const ns = useNamespace('app-topbar');
const { t } = useI18n();

const { routeNames: accountsModuleRouteNames } = useAccountsModuleRoutesNames();
const sessionStore = useSession();

const eventBus = inject(eventBusInjectionKey);

const darkMode = ref<boolean>(isDark.value);

let resolveFn: (value: boolean | PromiseLike<boolean>) => void;

const beforeThemeChange = (): Promise<boolean> => {
	return new Promise((resolve) => {
		resolveFn = resolve;
	});
};

const onSwitchTheme = (event: MouseEvent): void => {
	const isAppearanceTransition =
		// @ts-expect-error
		document.startViewTransition && !window.matchMedia('(prefers-reduced-motion: reduce)').matches;

	if (!isAppearanceTransition || !event) {
		resolveFn(true);

		return;
	}

	const x = event.clientX;
	const y = event.clientY;
	const endRadius = Math.hypot(Math.max(x, innerWidth - x), Math.max(y, innerHeight - y));

	// @ts-expect-error: Transition API
	const transition = document.startViewTransition(async () => {
		resolveFn(true);

		await nextTick();
	});

	transition.ready.then(() => {
		const clipPath = [`circle(0px at ${x}px ${y}px)`, `circle(${endRadius}px at ${x}px ${y}px)`];

		document.documentElement.animate(
			{
				clipPath: isDark.value ? [...clipPath].reverse() : clipPath,
			},
			{
				duration: 500,
				easing: 'ease-in',
				pseudoElement: isDark.value ? '::view-transition-old(root)' : '::view-transition-new(root)',
			}
		);
	});
};

const onToggleMenu = (): void => {
	emit('update:menuCollapsed', !props.menuCollapsed);
};

const onSignOut = (): void => {
	eventBus?.emit('userSigned', 'out');
};

const onLock = (): void => {
	eventBus?.emit('userLocked', true);
};

watch(
	(): boolean => darkMode.value,
	(): void => {
		toggleDark();
	}
);
</script>

<style rel="stylesheet/scss" lang="scss" scoped>
@import 'app-topbar.scss';
</style>
