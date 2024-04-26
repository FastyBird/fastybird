import { computed } from 'vue';

import { TinyColor } from '@ctrl/tinycolor';

import type { ComputedRef } from 'vue';
import type { MenuProps } from '../menu';

export const useMenuColor = (props: MenuProps): ComputedRef<string> => {
	return computed<string>((): string => {
		const color = props.backgroundColor;

		if (!color) {
			return '';
		}

		return new TinyColor(color).shade(20).toString();
	});
};
