import { computed } from 'vue';

import { breakpointsApplicationV5, useBreakpoints as vueUseBreakpoints } from '@vueuse/core';

import { UseBreakpoints } from '@/composables/types';

const breakpoints = vueUseBreakpoints(breakpointsApplicationV5);

export function useBreakpoints(): UseBreakpoints {
	const isExtraSmallDevice = computed<boolean>((): boolean => !breakpoints.md.value);
	const isSmallDevice = computed<boolean>((): boolean => !breakpoints.lg.value);

	return {
		isExtraSmallDevice,
		isSmallDevice,
	};
}
