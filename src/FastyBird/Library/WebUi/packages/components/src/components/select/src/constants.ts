import { OptionProxy } from './option';
import { SelectOption, SelectProps } from './select';

import type { InjectionKey } from 'vue';

export interface SelectGroupContext {
	disabled: boolean;
}

export const selectGroupContextKey: InjectionKey<SelectGroupContext> = Symbol('selectGroupContextKey');

export interface SelectContext {
	props: SelectProps;
	state: {
		inputValue: string;
		options: Map<string | number | boolean | object, OptionProxy>;
		cachedOptions: Map<string | number | boolean | object, OptionProxy>;
		disabledOptions: Map<string | number | boolean | object, OptionProxy>;
		optionValues: (string | number | boolean | object)[];
		selected: SelectOption | SelectOption[] | undefined;
		selectionWidth: number;
		calculatorWidth: number;
		collapseItemWidth: number;
		selectedLabel: string;
		hoveringIndex: number;
		previousQuery: string | null;
		inputHovering: boolean;
		menuVisibleOnFocus: boolean;
		isBeforeHide: boolean;
	};
	expanded: boolean;
	selectRef: HTMLElement;
	optionsArray: OptionProxy[];
	setSelected: () => void;
	onOptionCreate: (vm: OptionProxy) => void;
	onOptionDestroy: (key: string | number | boolean | object, vm: OptionProxy) => void;
	handleOptionSelect: (vm: OptionProxy) => void;
}

export const selectContextKey: InjectionKey<SelectContext> = Symbol('selectContextKey');
