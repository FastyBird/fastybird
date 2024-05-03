import type { ExtractPropTypes } from 'vue';

import { buildProps, definePropType } from '@fastybird/web-ui-utils';

export const RowJustify = ['start', 'center', 'end', 'space-around', 'space-between', 'space-evenly'] as const;

export const RowAlign = ['top', 'middle', 'bottom'] as const;

export const rowProps = buildProps({
	/**
	 * @description custom element tag
	 */
	tag: {
		type: definePropType<string>(String),
		default: 'div',
	},
	/**
	 * @description grid spacing
	 */
	gutter: {
		type: definePropType<number>(Number),
		default: 0,
	},
	/**
	 * @description horizontal alignment of flex layout
	 */
	justify: {
		type: definePropType<string>(String),
		values: RowJustify,
		default: 'start',
	},
	/**
	 * @description vertical alignment of flex layout
	 */
	align: {
		type: definePropType<string | undefined>(String),
		values: RowAlign,
	},
} as const);

export type RowProps = ExtractPropTypes<typeof rowProps>;
