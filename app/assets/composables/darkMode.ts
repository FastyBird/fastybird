import { useDark, useToggle } from '@vueuse/core';

export const isDark = useDark({
	storageKey: 'fb-theme-appearance',
});

export const toggleDark = useToggle(isDark);
