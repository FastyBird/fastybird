import { computed, getCurrentInstance, inject, provide, ref, unref } from 'vue';

import { debugWarn, keysOf } from '@fastybird/web-ui-utils';
import {
	SIZE_INJECTION_KEY,
	defaultInitialZIndex,
	localeContextKey,
	useLocale,
	useZIndex,
	zIndexContextKey,
	LocaleContext,
} from '@fastybird/web-ui-hooks';

import { ConfigProviderProps } from '../config-provider-props';
import { configProviderContextKey } from '../constants';

import type { MaybeRef } from '@vueuse/core';
import type { App, ComputedRef, Ref } from 'vue';
import type { ConfigProviderContext } from '../constants';

// this is meant to fix global methods like `FbMessage(opts)`, this way we can inject current locale
// into the component as default injection value.
const globalConfig = ref<ConfigProviderContext>();

export function useGlobalConfig<K extends keyof ConfigProviderContext, D extends ConfigProviderContext[K]>(
	key: K,
	defaultValue?: D
): Ref<Exclude<ConfigProviderContext[K], undefined> | D>;

export function useGlobalConfig(): Ref<ConfigProviderContext>;

export function useGlobalConfig(
	key?: keyof ConfigProviderContext,
	defaultValue = undefined
): ComputedRef<any> | Ref<Partial<ConfigProviderProps> | undefined> {
	const config = getCurrentInstance() ? inject(configProviderContextKey, globalConfig) : globalConfig;

	if (key) {
		return computed(() => config.value?.[key] ?? defaultValue);
	} else {
		return config;
	}
}

// for components like `FbMessage` `FbNotification` `FbMessageBox`.
export function useGlobalComponentSettings(sizeFallback?: MaybeRef<ConfigProviderContext['size']>): {
	locale: LocaleContext;
	zIndex: { initialZIndex: ComputedRef<number>; currentZIndex: ComputedRef<number>; nextZIndex: () => number };
	size: ComputedRef<any>;
} {
	const config = useGlobalConfig();

	const locale = useLocale(computed(() => config.value?.locale));
	const zIndex = useZIndex(computed(() => config.value?.zIndex || defaultInitialZIndex));
	const size = computed(() => unref(sizeFallback) || config.value?.size || '');

	provideGlobalConfig(computed(() => unref(config) || {}));

	return {
		locale,
		zIndex,
		size,
	};
}

export const provideGlobalConfig = (
	config: MaybeRef<ConfigProviderContext>,
	app?: App,
	global = false
): ComputedRef<Partial<ConfigProviderProps>> | undefined => {
	const inSetup = !!getCurrentInstance();
	const oldConfig = inSetup ? useGlobalConfig() : undefined;

	const provideFn = app?.provide ?? (inSetup ? provide : undefined);

	if (!provideFn) {
		debugWarn('provideGlobalConfig', 'provideGlobalConfig() can only be used inside setup().');

		return;
	}

	const context = computed(() => {
		const cfg = unref(config);

		if (!oldConfig?.value) {
			return cfg;
		}

		return mergeConfig(oldConfig.value, cfg);
	});

	if (app?.provide) {
		app.provide(configProviderContextKey, context);

		app.provide(
			localeContextKey,
			computed(() => context.value.locale)
		);

		app.provide(
			zIndexContextKey,
			computed(() => context.value.zIndex)
		);

		app.provide(SIZE_INJECTION_KEY, {
			size: computed(() => context.value.size || ''),
		});
	} else if (inSetup) {
		provide(configProviderContextKey, context);

		provide(
			localeContextKey,
			computed(() => context.value.locale)
		);

		provide(
			zIndexContextKey,
			computed(() => context.value.zIndex)
		);

		provide(SIZE_INJECTION_KEY, {
			size: computed(() => context.value.size || ''),
		});
	}

	if (global || !globalConfig.value) {
		globalConfig.value = context.value;
	}

	return context;
};

const mergeConfig = (a: ConfigProviderContext, b: ConfigProviderContext): ConfigProviderContext => {
	const keys = [...new Set([...keysOf(a), ...keysOf(b)])];
	const obj: Record<string, any> = {};

	for (const key of keys) {
		obj[key] = b[key] ?? a[key];
	}

	return obj;
};
