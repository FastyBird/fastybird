import { computed } from 'vue';

import { breakpointsBootstrapV5, useBreakpoints as vueUseBreakpoints } from '@vueuse/core';

import { UseBreakpoints } from './types';

const breakpoints = vueUseBreakpoints(breakpointsBootstrapV5);

export function useBreakpoints(): UseBreakpoints {
	const isXSDevice = computed<boolean>((): boolean => !breakpoints.md.value);
	const isSMDevice = computed<boolean>((): boolean => !breakpoints.lg.value);

	return {
		isXSDevice,
		isSMDevice,
	};
}
