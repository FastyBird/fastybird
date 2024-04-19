import { useFormItemLabelWidth } from './hooks';

import type { RuleItem, ValidateFieldsError } from 'async-validator';
import type { ComponentSize } from '@fastybird/web-ui-constants';
import type { Arrayable } from '@fastybird/web-ui-utils';
import { FormItemProp, FormItemValidateState } from './form-item';

export type FormLabelWidthContext = ReturnType<typeof useFormItemLabelWidth>;

export interface FormItemRule extends RuleItem {
	trigger?: Arrayable<string>;
}

export type FormValidationResult = Promise<boolean>;

export type FormValidateCallback = (isValid: boolean, invalidFields?: ValidateFieldsError) => void;

export interface FormItemContext {
	$el: HTMLDivElement | undefined;
	size: ComponentSize;
	validateState: FormItemValidateState;
	isGroup: boolean;
	label?: string;
	labelWidth: string | number;
	labelId: string;
	inputIds: string[];
	hasLabel: boolean;
	fieldValue: any;
	addInputId: (id: string) => void;
	removeInputId: (id: string) => void;
	validate: (trigger: string, callback?: FormValidateCallback) => FormValidationResult;
	prop: FormItemProp;
	required: boolean;
	rules: Arrayable<FormItemRule>;
	error?: string;
	validateStatus?: string;
	for?: string;
	inlineMessage: string | boolean;
	showMessage: boolean;
	resetField(): void;
	clearValidate(): void;
}
