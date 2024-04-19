import { buildProps, definePropType } from '@fastybird/web-ui-utils';
import { SelectContext } from './constants';

import type { ExtractPropTypes } from 'vue';

export const optionProps = buildProps({
	/**
	 * @description value of option
	 */
	value: {
		type: definePropType<string | number | boolean | object>([String, Number, Boolean, Object]),
		required: true,
	},
	/**
	 * @description label of option, same as `value` if omitted
	 */
	label: {
		type: definePropType<string | number | undefined>([String, Number]),
		default: undefined,
	},
	/**
	 * @description
	 */
	created: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
	/**
	 * @description whether option is disabled
	 */
	disabled: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
} as const);

export type OptionProps = ExtractPropTypes<typeof optionProps>;

export interface OptionProxy {
	id: string;
	value: OptionProps['value'];
	label: OptionProps['label'];
	created: OptionProps['created'];
	disabled: OptionProps['disabled'];
	state: {
		index: number;
		groupDisabled: boolean;
		visible: boolean;
		hover: boolean;
	};
	currentLabel: string;
	currentValue: string | number | boolean | object;
	isDisabled: boolean;
	itemSelected: boolean;
	select: SelectContext;
	updateOption: (query: string) => void;
	handleClick: () => void;
	handleHover: () => void;
	$el: HTMLElement;
}
