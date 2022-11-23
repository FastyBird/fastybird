import { computed } from 'vue';

import { breakpointsBootstrapV5, useBreakpoints as vueUseBreakpoints } from '@vueuse/core';

import { UseBreakpoints } from '@/types';

const breakpoints = vueUseBreakpoints(breakpointsBootstrapV5);

export function useBreakpoints(): UseBreakpoints {
	const isExtraSmallDevice = computed<boolean>((): boolean => !breakpoints.md.value);

	return {
		isExtraSmallDevice,
	};
}
