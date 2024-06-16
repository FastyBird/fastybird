import { buildProps, definePropType } from '@fastybird/web-ui-utils';

import type { ExtractPropTypes } from 'vue';

export const expandableBoxProps = buildProps({
	/**
	 * @description
	 */
	show: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
});

export type ExpandableBoxProps = ExtractPropTypes<typeof expandableBoxProps>;
