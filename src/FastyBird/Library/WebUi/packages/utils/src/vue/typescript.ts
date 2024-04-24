import type { AppContext, Plugin } from '@vue/runtime-core';

export type SFCWithInstall<T> = T & Plugin;

export type SFCInstallWithContext<T> = SFCWithInstall<T> & {
	_context: AppContext | null;
};
