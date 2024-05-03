import { computed } from 'vue';

import { useNamespace } from '@fastybird/web-ui-hooks';

import { useMenuColor } from './use-menu-color';

import type { ComputedRef } from 'vue';
import type { MenuProps } from '../menu';

export const useMenuCssVar = (props: MenuProps, level: number): ComputedRef<Record<string, string>> => {
	const ns = useNamespace('menu');

	return computed(() => {
		return ns.cssVarBlock({
			'text-color': props.textColor || '',
			'hover-text-color': props.textColor || '',
			'bg-color': props.backgroundColor || '',
			'hover-bg-color': useMenuColor(props).value || '',
			'active-color': props.activeTextColor || '',
			level: `${level}`,
		});
	});
};
